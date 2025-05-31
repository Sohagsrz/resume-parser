<?php
require 'vendor/autoload.php';

use Smalot\PdfParser\Parser;
require_once 'vendor/autoload.php';
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
// if no file then upload via html
if (!isset($_FILES['resume'])) {
    echo '<form method="post" enctype="multipart/form-data">
            <input type="file" name="resume" accept="application/pdf">
            <input type="submit" value="Upload">
          </form>';
    exit;
}
$pdf_path = $_FILES['resume']['tmp_name'];

?>



<?php
function extract_resume_data($pdf_path) {
    // Expanded section header variations
    $section_headers = [
        'skills' => [
            'Skills', 'Technical Skills', 'Tools', 'Core Competencies', 'Areas of Expertise', 'Key Skills', 'Expertise', 'Proficiencies', 'Skill Set'
        ],
        'education' => [
            'Education', 'Academic Background', 'Educational Qualifications', 'Education & Training', 'Academic History', 'Educational Background', 'Education Details', 'Academic Qualifications', 'Studies', 'Degrees'
        ],
        'experience' => [
            'Experience', 'Work Experience', 'Professional Experience', 'Employment History', 'Career History', 'Work History', 'Relevant Experience', 'Professional Background', 'Job Experience', 'Employment Experience', 'Work Profile', 'Professional Summary'
        ],
        'certifications' => [
            'Certifications', 'Certificates', 'Professional Certifications', 'Licenses', 'Accreditations', 'Awards', 'Achievements', 'Honors'
        ],
        'languages' => [
            'Languages', 'Language Proficiency', 'Spoken Languages', 'Language Skills', 'Languages Known'
        ]
    ];

    $parser = new Parser();
    $pdf = $parser->parseFile($pdf_path);
    $text = $pdf->getText();

    $data = [
        "name" => "",
        "email" => "",
        "phone" => "",
        "address" => "",
        "linkedin" => "",
        "github" => "",
        "twitter" => "",
        "facebook" => "",
        "instagram" => "",
        "website" => "",
        "skills" => [],
        "education" => [],
        "experience" => [],
        "certifications" => [],
        "languages" => []
    ];

    // Email
    if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $text, $matches)) {
        $data['email'] = $matches[0];
    }

    // Phone (global, using libphonenumber)
    $phoneUtil = PhoneNumberUtil::getInstance();
    $foundPhone = '';
    // Find all possible phone number candidates (libphonenumber will validate them)
    if (preg_match_all('/(\+\d{1,3}[\s\-]?)?(\(?\d{1,4}\)?[\s\-]?)?\d{3,4}[\s\-]?\d{3,4}[\s\-]?\d{0,4}/', $text, $matches)) {
        foreach ($matches[0] as $candidate) {
            $candidate = trim($candidate);
            // Remove spaces, dashes, and parentheses for normalization
            $normalized = preg_replace('/[\s\-\(\)]/', '', $candidate);
            try {
                // Try parsing with unknown region ("ZZ")
                $numberProto = $phoneUtil->parse($normalized, 'ZZ');
                if ($phoneUtil->isValidNumber($numberProto)) {
                    $foundPhone = $phoneUtil->format($numberProto, \libphonenumber\PhoneNumberFormat::E164);
                    break;
                }
            } catch (NumberParseException $e) {
                // Ignore and continue
            }
        }
    }
    $data['phone'] = $foundPhone;

    // Social handles regex patterns (collect all matches)
    $social_patterns = [
        'linkedin' => '/https?:\/\/(www\.)?linkedin\.com\/[\S]+/i',
        'github' => '/https?:\/\/(www\.)?github\.com\/[\S]+/i',
        'twitter' => '/https?:\/\/(www\.)?(twitter|x)\.com\/[\S]+/i',
        'facebook' => '/https?:\/\/(www\.)?facebook\.com\/[\S]+/i',
        'instagram' => '/https?:\/\/(www\.)?instagram\.com\/[\S]+/i',
        'stackoverflow' => '/https?:\/\/(www\.)?stackoverflow\.com\/[\S]+/i',
        'dribbble' => '/https?:\/\/(www\.)?dribbble\.com\/[\S]+/i',
        'behance' => '/https?:\/\/(www\.)?behance\.net\/[\S]+/i',
        'medium' => '/https?:\/\/(www\.)?medium\.com\/[\S]+/i',
        'youtube' => '/https?:\/\/(www\.)?(youtube\.com|youtu\.be)\/[\S]+/i',
        'tiktok' => '/https?:\/\/(www\.)?tiktok\.com\/[\S]+/i',
        'pinterest' => '/https?:\/\/(www\.)?pinterest\.com\/[\S]+/i',
        'telegram' => '/https?:\/\/(t\.me|telegram\.me)\/[\S]+/i',
        'whatsapp' => '/https?:\/\/(wa\.me|api\.whatsapp\.com)\/[\S]+/i',
        'blog' => '/https?:\/\/(www\.)?[^\s]+blog\.[a-z]{2,}(\/\S*)?/i',
        'website' => '/https?:\/\/(?!www\.(linkedin|github|twitter|x|facebook|instagram|stackoverflow|dribbble|behance|medium|youtube|youtu|tiktok|pinterest|telegram|whatsapp|blog)\.com)[\w\.-]+\.[a-z]{2,}(\/\S*)?/i',
    ];
    foreach ($social_patterns as $key => $pattern) {
        if (preg_match_all($pattern, $text, $matches)) {
            $unique = array_values(array_unique($matches[0]));
            $data[$key] = count($unique) === 1 ? $unique[0] : $unique;
        }
    }

    // Split text into lines
    $lines = array_map('trim', explode("\n", $text));
    $lines = array_filter($lines, function($line) { return $line !== ''; });

    // Heuristic for name: first non-header, non-empty line near the top, not an email or phone
    $possible_name = '';
    $max_lines_to_check = 12;
    $checked = 0;
    foreach ($lines as $line) {
        if ($checked++ > $max_lines_to_check) break;
        $is_header = false;
        foreach ($section_headers as $headers) {
            foreach ($headers as $header) {
                if (stripos($line, $header) !== false) {
                    $is_header = true;
                    break 2;
                }
            }
        }
        // Skip if line is an email
        if (preg_match('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}/', $line)) continue;
        // Skip if line is a phone number
        if ($data['phone'] && strpos($line, $data['phone']) !== false) continue;
        // Look for 2-4 words, all capitalized or title case
        $words = preg_split('/\s+/', trim($line));
        if (count($words) >= 2 && count($words) <= 4) {
            $is_name_like = true;
            foreach ($words as $w) {
                if (!preg_match('/^[A-Z][a-z\'\-\.]+$/', $w) && !preg_match('/^[A-Z]+$/', $w)) {
                    $is_name_like = false;
                    break;
                }
            }
            if ($is_name_like) {
                $possible_name = $line;
                break;
            }
        }
    }
    $data['name'] = $possible_name;

    // Address (look for keywords)
    foreach ($lines as $line) {
        if (stripos($line, 'address') !== false) {
            $data['address'] = trim(str_ireplace('address', '', $line));
            break;
        }
    }

    // Improved block-based section parsing
    $section_blocks = [];
    $current_section = '';
    foreach ($lines as $line) {
        $found_section = false;
        foreach ($section_headers as $section => $headers) {
            foreach ($headers as $header) {
                // Allow for headers with/without colon/dash, on their own line, or with extra spaces
                if (preg_match('/^\s*' . preg_quote($header, '/') . '\s*[:\-]?\s*$/i', $line) ||
                    preg_match('/^\s*' . preg_quote($header, '/') . '\s*[:\-]/i', $line)) {
                    $current_section = $section;
                    $section_blocks[$current_section] = [];
                    $found_section = true;
                    break 2;
                }
            }
        }
        if ($current_section && !$found_section) {
            $section_blocks[$current_section][] = $line;
        }
    }

    // Predefined list of common skills/technologies and soft skills
    $common_skills = [
        // Technical skills
        'PHP', 'Laravel', 'Symfony', 'CodeIgniter', 'JavaScript', 'TypeScript', 'React', 'Vue', 'Angular', 'Node.js', 'Express', 'HTML', 'CSS', 'SASS', 'LESS', 'Bootstrap', 'Tailwind', 'jQuery', 'MySQL', 'PostgreSQL', 'MongoDB', 'SQLite', 'Redis', 'Docker', 'Kubernetes', 'AWS', 'Azure', 'GCP', 'Linux', 'Git', 'CI/CD', 'Jenkins', 'REST', 'GraphQL', 'SOAP', 'Python', 'Java', 'C#', 'C++', 'Go', 'Ruby', 'Swift', 'Kotlin', 'Objective-C', 'Flutter', 'Dart', 'Figma', 'Photoshop', 'Illustrator', 'SEO', 'WordPress', 'Shopify', 'Magento', 'Salesforce', 'Agile', 'Scrum', 'Jira', 'Trello', 'Slack', 'Notion', 'Firebase', 'Selenium', 'JUnit', 'Mocha', 'Chai', 'Cypress', 'Pandas', 'NumPy', 'TensorFlow', 'PyTorch', 'Machine Learning', 'Data Science', 'AI', 'NLP', 'Big Data', 'Hadoop', 'Spark', 'Tableau', 'Power BI', 'Business Analysis', 'Project Management', 'QA', 'Testing', 'Automation', 'Security', 'Penetration Testing', 'DevOps', 'Mobile Development', 'iOS', 'Android', 'UI/UX', 'Design', 'Cloud', 'Networking', 'Virtualization', 'Blockchain', 'Cryptocurrency', 'Game Development', 'Unity', 'Unreal Engine',
        // Non-technical/soft skills
        'Communication', 'Leadership', 'Problem Solving', 'Teamwork', 'Time Management', 'Adaptability', 'Creativity', 'Critical Thinking', 'Public Speaking', 'Writing', 'Research', 'Customer Service', 'Marketing', 'Sales', 'Finance', 'Accounting', 'HR', 'Operations', 'Logistics', 'Supply Chain', 'Negotiation', 'Conflict Resolution', 'Decision Making', 'Collaboration', 'Organization', 'Attention to Detail', 'Multitasking', 'Self-Motivation', 'Work Ethic', 'Empathy', 'Emotional Intelligence', 'Presentation', 'Mentoring', 'Coaching', 'Planning', 'Strategic Thinking', 'Resourcefulness', 'Flexibility', 'Active Listening', 'Persuasion', 'Delegation', 'Stress Management', 'Goal Setting', 'Networking', 'Supervision', 'Training', 'Recruitment', 'Scheduling', 'Budgeting', 'Data Analysis', 'Reporting', 'Event Planning', 'Customer Relationship Management', 'Supervisory Skills', 'Process Improvement', 'Quality Assurance', 'Risk Management', 'Compliance', 'Procurement', 'Inventory Management', 'Business Development', 'Brand Management', 'Digital Marketing', 'Content Creation', 'Copywriting', 'Editing', 'Social Media', 'Market Research', 'Product Management', 'Client Management', 'Stakeholder Management', 'Fundraising', 'Grant Writing', 'Volunteer Management', 'Teaching', 'Tutoring', 'Instructional Design', 'Curriculum Development', 'Classroom Management', 'E-Learning', 'Translation', 'Transcription', 'Legal Research', 'Paralegal', 'Litigation Support', 'Case Management', 'Medical Terminology', 'Patient Care', 'Clinical Research', 'Healthcare Administration', 'Lab Skills', 'Safety Compliance', 'First Aid', 'CPR', 'OSHA Compliance', 'Food Safety', 'Bartending', 'Cooking', 'Housekeeping', 'Maintenance', 'Landscaping', 'Driving', 'Forklift Operation', 'Machinery Operation', 'Welding', 'Carpentry', 'Plumbing', 'Electrical', 'HVAC', 'Construction', 'Blueprint Reading', 'AutoCAD', 'Drafting', 'Surveying', 'Painting', 'Photography', 'Video Editing', 'Music Production', 'Acting', 'Dance', 'Art', 'Sculpture', 'Graphic Design', 'Fashion Design', 'Interior Design', 'Jewelry Making', 'Crafts', 'Sewing', 'Tailoring', 'Makeup', 'Hair Styling', 'Nail Art', 'Fitness Training', 'Yoga', 'Pilates', 'Sports Coaching', 'Athletics', 'Personal Training', 'Nutrition', 'Diet Planning', 'Wellness Coaching', 'Life Coaching', 'Travel Planning', 'Tour Guiding', 'Flight Attendant', 'Ticketing', 'Reservation Management', 'Hospitality', 'Hotel Management', 'Front Desk', 'Concierge', 'Event Coordination', 'Catering', 'Bartending', 'Barista', 'Retail', 'Cashiering', 'Merchandising', 'Inventory Control', 'Visual Merchandising', 'Loss Prevention', 'Store Management', 'Customer Retention', 'Upselling', 'Cross-Selling', 'Telemarketing', 'Cold Calling', 'Lead Generation', 'Appointment Setting', 'Order Processing', 'Shipping', 'Receiving', 'Warehouse Management', 'Forklift Operation', 'Logistics Coordination', 'Route Planning', 'Fleet Management', 'Dispatching', 'Customs Compliance', 'Import/Export', 'Supply Chain Optimization', 'Vendor Management', 'Purchasing', 'Sourcing', 'Contract Negotiation', 'Cost Reduction', 'Expense Management', 'Financial Analysis', 'Bookkeeping', 'Payroll', 'Tax Preparation', 'Audit', 'Financial Reporting', 'Investment Analysis', 'Portfolio Management', 'Risk Assessment', 'Insurance', 'Claims Processing', 'Underwriting', 'Loan Processing', 'Mortgage', 'Real Estate', 'Property Management', 'Appraisal', 'Leasing', 'Tenant Relations', 'Facilities Management', 'Security Management', 'Emergency Response', 'Disaster Recovery', 'Business Continuity', 'IT Support', 'Help Desk', 'Technical Support', 'System Administration', 'Network Administration', 'Database Administration', 'Software Installation', 'Hardware Troubleshooting', 'Mobile Device Management', 'Cloud Administration', 'Website Maintenance', 'SEO Optimization', 'SEM', 'PPC', 'Affiliate Marketing', 'Email Marketing', 'CRM', 'ERP', 'SAP', 'Oracle', 'Microsoft Office', 'Excel', 'Word', 'PowerPoint', 'Outlook', 'Access', 'Google Suite', 'Zoom', 'Webex', 'Teams', 'Skype', 'Slack', 'Trello', 'Asana', 'Monday.com', 'Basecamp', 'ClickUp', 'Smartsheet', 'Wrike', 'Airtable', 'Notion', 'Miro', 'Lucidchart', 'Canva', 'Hootsuite', 'Buffer', 'Sprout Social', 'Mailchimp', 'Constant Contact', 'HubSpot', 'Salesforce', 'Zendesk', 'Freshdesk', 'Intercom', 'LiveChat', 'Drift', 'Zoho', 'Pipedrive', 'Insightly', 'Bitrix24', 'SugarCRM', 'Nimble', 'Capsule', 'Streak', 'Agile CRM', 'Keap', 'Infusionsoft', 'Marketo', 'Pardot', 'Eloqua', 'Act-On', 'SharpSpring', 'Sendinblue', 'GetResponse', 'AWeber', 'Campaign Monitor', 'iContact', 'Benchmark Email', 'Moosend', 'Omnisend', 'Mailjet', 'Mailerlite', 'ConvertKit', 'Klaviyo', 'ActiveCampaign', 'Drip', 'Emma', 'MailerQ', 'Postmark', 'Mandrill', 'SendGrid', 'Amazon SES', 'Mailgun', 'SparkPost', 'SMTP.com', 'SocketLabs', 'Mailtrap', 'GlockApps', 'Litmus', 'Email on Acid', 'Testi@','Testo@'
    ];

    // Skills
    $skills_found = [];
    $skills_section_skills = [];
    // 1. From skills section (if present)
    if (!empty($section_blocks['skills'])) {
        $skills_text = implode(' ', $section_blocks['skills']);
        // Split on comma, newlines, or multiple spaces
        $skills_section_skills = preg_split('/,|\n|\s{2,}/', $skills_text);
        $skills_section_skills = array_map('trim', array_filter($skills_section_skills));
        $skills_found = $skills_section_skills;
    }
    // 2. From entire resume text using predefined list
    $text_lower = strtolower($text);
    foreach ($common_skills as $skill) {
        if (stripos($text_lower, strtolower($skill)) !== false) {
            $skills_found[] = $skill;
        }
    }
    // Ensure uniqueness and reindex
    $data['skills'] = array_values(array_unique($skills_found));

    // Education
    $data['education'] = [];
    if (!empty($section_blocks['education'])) {
        $edu_lines = $section_blocks['education'];
        $edu_entry = ['degree' => '', 'institution' => '', 'year' => ''];
        foreach ($edu_lines as $line) {
            // Try to extract degree, institution, and year from the same line
            if (preg_match('/(?P<degree>Bachelor|BSc|MSc|Master|PhD|Diploma|HSC|SSC|High School|Associate|BA|BS|MA|MS|MBA|Doctor|Certificate)[^,\n]*,?\s*(?P<institution>[^,\d\n]+)?[,\s]*(?P<year>\d{4})?/i', $line, $m)) {
                $edu_entry = [
                    'degree' => trim($m['degree'] ?? ''),
                    'institution' => trim($m['institution'] ?? ''),
                    'year' => trim($m['year'] ?? '')
                ];
                $data['education'][] = $edu_entry;
                $edu_entry = ['degree' => '', 'institution' => '', 'year' => ''];
            } else {
                // Try to extract year
                if (preg_match('/\b(\d{4})\b/', $line, $m)) {
                    $edu_entry['year'] = $m[1];
                }
                // Try to extract degree
                if (preg_match('/(Bachelor|BSc|MSc|Master|PhD|Diploma|HSC|SSC|High School|Associate|BA|BS|MA|MS|MBA|Doctor|Certificate)/i', $line, $m)) {
                    $edu_entry['degree'] = $m[1];
                }
                // Otherwise, treat as institution if not empty
                elseif (trim($line) !== '') {
                    $edu_entry['institution'] = trim($line);
                }
                // If at least one field is filled, add entry
                if ($edu_entry['degree'] || $edu_entry['institution'] || $edu_entry['year']) {
                    $data['education'][] = $edu_entry;
                    $edu_entry = ['degree' => '', 'institution' => '', 'year' => ''];
                }
            }
        }
    }

    // Experience
    $data['experience'] = [];
    if (!empty($section_blocks['experience'])) {
        $exp_lines = $section_blocks['experience'];
        $exp_entry = ['job_title' => '', 'company' => '', 'duration' => '', 'description' => ''];
        $desc_buffer = '';
        foreach ($exp_lines as $line) {
            // Try to extract job title, company, and duration from the same line
            if (preg_match('/^(?P<job_title>[A-Za-z \-\/,&]+) at (?P<company>[^,\n]+),?\s*(?P<duration>(\w{3,9} \d{4}|\d{4})(\s*[-–—]\s*(\w{3,9} \d{4}|Present|\d{4}))?)/i', $line, $m)) {
                $exp_entry = [
                    'job_title' => trim($m['job_title'] ?? ''),
                    'company' => trim($m['company'] ?? ''),
                    'duration' => trim($m['duration'] ?? ''),
                    'description' => ''
                ];
                if ($desc_buffer) {
                    $exp_entry['description'] = trim($desc_buffer);
                    $desc_buffer = '';
                }
                $data['experience'][] = $exp_entry;
                $exp_entry = ['job_title' => '', 'company' => '', 'duration' => '', 'description' => ''];
            } else if (preg_match('/^(?P<job_title>[A-Za-z \-\/,&]+)[,\-\|]+\s*(?P<company>[^,\n]+)[,\-\|]+\s*(?P<duration>(\w{3,9} \d{4}|\d{4})(\s*[-–—]\s*(\w{3,9} \d{4}|Present|\d{4}))?)/i', $line, $m)) {
                $exp_entry = [
                    'job_title' => trim($m['job_title'] ?? ''),
                    'company' => trim($m['company'] ?? ''),
                    'duration' => trim($m['duration'] ?? ''),
                    'description' => ''
                ];
                if ($desc_buffer) {
                    $exp_entry['description'] = trim($desc_buffer);
                    $desc_buffer = '';
                }
                $data['experience'][] = $exp_entry;
                $exp_entry = ['job_title' => '', 'company' => '', 'duration' => '', 'description' => ''];
            } else if (preg_match('/^(?P<job_title>[A-Za-z \-\/,&]+)$/', $line, $m)) {
                if ($exp_entry['job_title'] || $exp_entry['company'] || $exp_entry['duration'] || $exp_entry['description']) {
                    $exp_entry['description'] = trim($desc_buffer);
                    $data['experience'][] = $exp_entry;
                    $exp_entry = ['job_title' => '', 'company' => '', 'duration' => '', 'description' => ''];
                    $desc_buffer = '';
                }
                $exp_entry['job_title'] = trim($m['job_title']);
            } else if (preg_match('/^(?P<company>[A-Za-z0-9 \-\/,&\.]+)$/', $line, $m)) {
                $exp_entry['company'] = trim($m['company']);
            } else if (preg_match('/(?P<duration>(\w{3,9} \d{4}|\d{4})(\s*[-–—]\s*(\w{3,9} \d{4}|Present|\d{4}))?)/', $line, $m)) {
                $exp_entry['duration'] = trim($m['duration']);
            } else if (trim($line) !== '') {
                $desc_buffer .= ' ' . trim($line);
            }
        }
        // Add last entry if any
        if ($exp_entry['job_title'] || $exp_entry['company'] || $exp_entry['duration'] || $desc_buffer) {
            $exp_entry['description'] = trim($desc_buffer);
            $data['experience'][] = $exp_entry;
        }
    }

    // Predefined lists for certifications and languages
    $common_certifications = [
        'AWS Certified', 'Azure Certified', 'Google Cloud Certified', 'PMP', 'Scrum Master', 'ITIL', 'Cisco', 'CCNA', 'CCNP', 'CompTIA', 'Oracle Certified', 'Microsoft Certified', 'Certified Ethical Hacker', 'CISSP', 'CPA', 'CFA', 'Six Sigma', 'TOGAF', 'ISTQB', 'SAP Certified', 'Adobe Certified', 'AutoCAD Certified', 'First Aid', 'CPR', 'Food Safety', 'ServSafe', 'Project Management Professional', 'Certified Public Accountant', 'Chartered Financial Analyst', 'Certified ScrumMaster', 'Google Analytics', 'Meta Certified', 'HubSpot Certified', 'Salesforce Certified', 'Red Hat Certified', 'Linux+', 'Network+', 'Security+', 'Google Ads', 'Google Digital Garage', 'Coursera', 'Udemy', 'edX', 'LinkedIn Learning', 'Meta Frontend Certificate', 'AWS Cloud Practitioner', 'AWS Solutions Architect', 'AWS Developer', 'AWS SysOps', 'AWS DevOps', 'AWS Security', 'AWS Advanced Networking', 'AWS Machine Learning', 'AWS Alexa Skill Builder', 'AWS Database', 'AWS Data Analytics', 'AWS SAP', 'AWS Specialty', 'AWS Certified Cloud Practitioner', 'AWS Certified Solutions Architect', 'AWS Certified Developer', 'AWS Certified SysOps Administrator', 'AWS Certified DevOps Engineer', 'AWS Certified Security', 'AWS Certified Advanced Networking', 'AWS Certified Machine Learning', 'AWS Certified Alexa Skill Builder', 'AWS Certified Database', 'AWS Certified Data Analytics', 'AWS Certified SAP', 'AWS Certified Specialty'
    ];
    $common_languages = [
        'English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese', 'Russian', 'Chinese', 'Mandarin', 'Cantonese', 'Japanese', 'Korean', 'Hindi', 'Bengali', 'Urdu', 'Arabic', 'Turkish', 'Vietnamese', 'Polish', 'Dutch', 'Greek', 'Czech', 'Swedish', 'Norwegian', 'Finnish', 'Danish', 'Hungarian', 'Romanian', 'Thai', 'Indonesian', 'Malay', 'Filipino', 'Hebrew', 'Persian', 'Swahili', 'Zulu', 'Afrikaans', 'Serbian', 'Croatian', 'Slovak', 'Slovenian', 'Bulgarian', 'Ukrainian', 'Lithuanian', 'Latvian', 'Estonian', 'Georgian', 'Armenian', 'Azerbaijani', 'Kazakh', 'Uzbek', 'Turkmen', 'Kyrgyz', 'Tajik', 'Pashto', 'Sinhala', 'Tamil', 'Nepali', 'Burmese', 'Khmer', 'Lao', 'Mongolian', 'Tibetan', 'Maori', 'Samoan', 'Tongan', 'Fijian', 'Hawaiian', 'Haitian Creole', 'Javanese', 'Sundanese', 'Tagalog', 'Cebuano', 'Ilocano', 'Waray', 'Hiligaynon', 'Bikol', 'Kapampangan', 'Pangasinan', 'Maranao', 'Maguindanao', 'Tausug', 'Yakan', 'Chavacano', 'Bangla', 'Bengali', 'Punjabi', 'Gujarati', 'Marathi', 'Telugu', 'Kannada', 'Malayalam', 'Odia', 'Assamese', 'Maithili', 'Santali', 'Konkani', 'Kashmiri', 'Sindhi', 'Dogri', 'Manipuri', 'Bodo', 'Santhali', 'Nepali', 'Bhili', 'Gondi', 'Tulu', 'Kurukh', 'Munda', 'Ho', 'Khasi', 'Mizo', 'Ao', 'Lotha', 'Sema', 'Angami', 'Phom', 'Konyak', 'Chakhesang', 'Chang', 'Yimchungru', 'Zeme', 'Pochury', 'Rengma', 'Khiamniungan', 'Kuki', 'Paite', 'Hmar', 'Vaiphei', 'Simte', 'Gangte', 'Kom', 'Lamkang', 'Anal', 'Maring', 'Mao', 'Poumai', 'Thangal', 'Zeliang', 'Liangmai', 'Rongmei', 'Inpui', 'Aimol', 'Chothe', 'Monsang', 'Moyon', 'Sangtam', 'Yimchunger', 'Chang', 'Phom', 'Konyak', 'Lotha', 'Sumi', 'Ao', 'Angami', 'Rengma', 'Zeme', 'Pochury', 'Khiamniungan', 'Chakhesang', 'Pangasinan', 'Kapampangan', 'Ilocano', 'Cebuano', 'Hiligaynon', 'Waray', 'Bikol', 'Tagalog', 'Filipino', 'Sundanese', 'Javanese', 'Balinese', 'Madurese', 'Minangkabau', 'Bugis', 'Acehnese', 'Banjarese', 'Batak', 'Rejang', 'Lampung', 'Sasak', 'Makassarese', 'Mandar', 'Makasar', 'Buginese', 'Toraja', 'Muna', 'Wolio', 'Tolaki', 'Buton', 'Bonerate', 'Saluan', 'Banggai', 'Mori', 'Bungku', 'Pamona', 'Kaili', 'Buol', 'Gorontalo', 'Mongondow', 'Ternate', 'Tidore', 'Tobelo', 'Galela', 'Sahu', 'Loloda', 'Kao', 'Morotai', 'Buru', 'Ambonese', 'Seram', 'Tanimbar', 'Aru', 'Yapen', 'Biak', 'Waropen', 'Sentani', 'Dani', 'Asmat', 'Muyu', 'Mandobo', 'Awyu', 'Marind', 'Yei', 'Yali', 'Kimyal', 'Korowai', 'Mek', 'Ngalum', 'Ok', 'Ketengban', 'Baliem', 'Damal', 'Moni', 'Wano', 'Yapen', 'Biak', 'Waropen', 'Sentani', 'Dani', 'Asmat', 'Muyu', 'Mandobo', 'Awyu', 'Marind', 'Yei', 'Yali', 'Kimyal', 'Korowai', 'Mek', 'Ngalum', 'Ok', 'Ketengban', 'Baliem', 'Damal', 'Moni', 'Wano', 'Yapen', 'Biak', 'Waropen', 'Sentani', 'Dani', 'Asmat', 'Muyu', 'Mandobo', 'Awyu', 'Marind', 'Yei', 'Yali', 'Kimyal', 'Korowai', 'Mek', 'Ngalum', 'Ok', 'Ketengban', 'Baliem', 'Damal', 'Moni', 'Wano'
    ];

    // Certifications
    $certs_found = [];
    if (!empty($section_blocks['certifications'])) {
        $certs = preg_split('/,|\n|\s{2,}/', implode(' ', $section_blocks['certifications']));
        $certs_found = array_map('trim', array_filter($certs));
    }
    foreach ($common_certifications as $cert) {
        if (stripos($text_lower, strtolower($cert)) !== false) {
            $certs_found[] = $cert;
        }
    }
    $data['certifications'] = array_values(array_unique($certs_found));

    // Languages
    $langs_found = [];
    if (!empty($section_blocks['languages'])) {
        $langs = preg_split('/,|\n|\s{2,}/', implode(' ', $section_blocks['languages']));
        $langs_found = array_map('trim', array_filter($langs));
    }
    foreach ($common_languages as $lang) {
        if (stripos($text_lower, strtolower($lang)) !== false) {
            $langs_found[] = $lang;
        }
    }
    $data['languages'] = array_values(array_unique($langs_found));

    // Logging and fallback extraction for missing sections
    $log = [];

    // Fallback for education
    if (empty($data['education'])) {
        $log[] = 'Education section missing or empty. Attempting fallback extraction.';
        if (preg_match_all('/(?P<degree>Bachelor|BSc|MSc|Master|PhD|Diploma|HSC|SSC|High School|Associate|BA|BS|MA|MS|MBA|Doctor|Certificate)[^,\n]*,?\s*(?P<institution>[^,\d\n]+)?[,\s]*(?P<year>\d{4})?/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $data['education'][] = [
                    'degree' => trim($m['degree'] ?? ''),
                    'institution' => trim($m['institution'] ?? ''),
                    'year' => trim($m['year'] ?? '')
                ];
            }
        }
    }

    // Fallback for experience
    if (empty($data['experience'])) {
        $log[] = 'Experience section missing or empty. Attempting fallback extraction.';
        if (preg_match_all('/(?P<job_title>[A-Za-z \-\/,&]+) at (?P<company>[^,\n]+),?\s*(?P<duration>(\w{3,9} \d{4}|\d{4})(\s*[-–—]\s*(\w{3,9} \d{4}|Present|\d{4}))?)/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $data['experience'][] = [
                    'job_title' => trim($m['job_title'] ?? ''),
                    'company' => trim($m['company'] ?? ''),
                    'duration' => trim($m['duration'] ?? ''),
                    'description' => ''
                ];
            }
        }
    }

    // Fallback for skills
    if (empty($data['skills'])) {
        $log[] = 'Skills section missing or empty. Attempting fallback extraction.';
        if (preg_match_all('/\b([A-Za-z][A-Za-z0-9\+\#\.\- ]{2,})\b/', $text, $matches)) {
            foreach ($matches[1] as $word) {
                if (!in_array($word, $data['skills'])) {
                    $data['skills'][] = $word;
                }
            }
        }
    }

    // Fallback for certifications
    if (empty($data['certifications'])) {
        $log[] = 'Certifications section missing or empty. Attempting fallback extraction.';
        if (preg_match_all('/\b(certified|certificate|certification|diploma|award|license|accreditation)\b[^\n,]*/i', $text, $matches)) {
            foreach ($matches[0] as $cert) {
                $data['certifications'][] = trim($cert);
            }
        }
    }

    // Fallback for languages
    if (empty($data['languages'])) {
        $log[] = 'Languages section missing or empty. Attempting fallback extraction.';
        if (preg_match_all('/\b([A-Z][a-z]+)\b/', $text, $matches)) {
            foreach ($matches[1] as $lang) {
                if (!in_array($lang, $data['languages'])) {
                    $data['languages'][] = $lang;
                }
            }
        }
    }

    // Output log for debugging (optional, comment out in production)
    if (!empty($log)) {
        file_put_contents('parser_log.txt', implode("\n", $log));
    }

    return $data;
}

// Example usage:
$pdf_path = $pdf_path;
$result = extract_resume_data($pdf_path);
echo json_encode($result, JSON_PRETTY_PRINT); 