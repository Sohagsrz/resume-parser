<?php
namespace Sohagsrz\ResumeParser;

use Smalot\PdfParser\Parser;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

class ResumeParser
{
    public static function parse($pdf_path)
    {
        // --- Begin extraction logic (from your improved parse_resume.php) ---
        $parser = new Parser();
        $pdf = $parser->parseFile($pdf_path);
        $text = $pdf->getText();

        $data = [
            "name" => "",
            "email" => "",
            "phone" => "",
            "address" => "",
            "linkedin" => [],
            "github" => [],
            "twitter" => [],
            "facebook" => [],
            "instagram" => [],
            "stackoverflow" => [],
            "dribbble" => [],
            "behance" => [],
            "medium" => [],
            "youtube" => [],
            "tiktok" => [],
            "pinterest" => [],
            "telegram" => [],
            "whatsapp" => [],
            "blog" => [],
            "website" => [],
            "skills" => [],
            "education" => [],
            "experience" => [],
            "certifications" => [],
            "languages" => []
        ];

        // Section header variations (expanded)
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

        // Email
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $text, $matches)) {
            $data['email'] = $matches[0];
        }

        // Phone (global, using libphonenumber)
        $phoneUtil = PhoneNumberUtil::getInstance();
        $foundPhone = '';
        if (preg_match_all('/(\+\d{1,3}[\s\-]?)?(\(?\d{1,4}\)?[\s\-]?)?\d{3,4}[\s\-]?\d{3,4}[\s\-]?\d{0,4}/', $text, $matches)) {
            foreach ($matches[0] as $candidate) {
                $candidate = trim($candidate);
                $normalized = preg_replace('/[\s\-\(\)]/', '', $candidate);
                try {
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
                $data[$key] = $unique;
            }
        }

        // Split text into lines
        $lines = array_map('trim', explode("\n", $text));
        $lines = array_filter($lines, function($line) { return $line !== ''; });

        // Helper: section header and noise patterns
        $noise_patterns = '/^(Page \d+ of \d+|Summary|Contact|Experience|Education|Languages|Certifications|Skills|Top Skills|Personal|LinkedIn|Mobile|Email|Website|Address|\d{4}|[A-Z ]{4,}|^\s*$)/i';

        // Improved name extraction
        $possible_name = '';
        $max_lines_to_check = 12;
        $checked = 0;
        foreach ($lines as $line) {
            if ($checked++ > $max_lines_to_check) break;
            $is_header = false;
            if (preg_match($noise_patterns, $line)) continue;
            foreach ($section_headers as $headers) {
                foreach ($headers as $header) {
                    if (stripos($line, $header) !== false) {
                        $is_header = true;
                        break 2;
                    }
                }
            }
            if ($is_header) continue;
            if (preg_match('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}/', $line)) continue;
            if ($data['phone'] && strpos($line, $data['phone']) !== false) continue;
            $words = preg_split('/\s+/', trim($line));
            if (count($words) >= 2 && count($words) <= 4 && strlen($line) > 4 && strlen($line) < 50) {
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

        // Address (look for keywords, skip noise)
        foreach ($lines as $line) {
            if (preg_match($noise_patterns, $line)) continue;
            if (stripos($line, 'address') !== false) {
                $data['address'] = trim(str_ireplace('address', '', $line));
                break;
            }
        }

        // Improved block-based section parsing (stop at next section header, skip noise)
        $section_blocks = [];
        $current_section = '';
        foreach ($lines as $line) {
            $found_section = false;
            if (preg_match($noise_patterns, $line)) continue;
            foreach ($section_headers as $section => $headers) {
                foreach ($headers as $header) {
                    if (preg_match('/^\s*' . preg_quote($header, '/') . '\s*[:\-]?\s*$/i', $line) ||
                        preg_match('/^\s*' . preg_quote($header, '/') . '\s*[:\-]/i', $line)) {
                        $current_section = $section;
                        $section_blocks[$current_section] = [];
                        $found_section = true;
                        break 2;
                    }
                }
            }
            if ($found_section) continue;
            // Stop section if another header is found
            foreach ($section_headers as $section => $headers) {
                foreach ($headers as $header) {
                    if (preg_match('/^\s*' . preg_quote($header, '/') . '\s*[:\-]?\s*$/i', $line) ||
                        preg_match('/^\s*' . preg_quote($header, '/') . '\s*[:\-]/i', $line)) {
                        $current_section = '';
                        break 2;
                    }
                }
            }
            if ($current_section) {
                $section_blocks[$current_section][] = $line;
            }
        }

        // Skills (section only, no fallback)
        $skills_found = [];
        if (!empty($section_blocks['skills'])) {
            $skills_text = implode(' ', $section_blocks['skills']);
            $skills_section_skills = preg_split('/,|\n|\s{2,}/', $skills_text);
            foreach ($skills_section_skills as $skill) {
                $skill = trim($skill);
                if ($skill && strlen($skill) < 30 && !preg_match($noise_patterns, $skill)) {
                    $skills_found[] = $skill;
                }
            }
        }
        $data['skills'] = array_values(array_unique($skills_found));

        // Certifications (section only, no fallback)
        $certs_found = [];
        if (!empty($section_blocks['certifications'])) {
            $certs = preg_split('/,|\n|\s{2,}/', implode(' ', $section_blocks['certifications']));
            foreach ($certs as $cert) {
                $cert = trim($cert);
                if ($cert && strlen($cert) < 60 && !preg_match($noise_patterns, $cert)) {
                    $certs_found[] = $cert;
                }
            }
        }
        $data['certifications'] = array_values(array_unique($certs_found));

        // Languages (section only, no fallback)
        $langs_found = [];
        if (!empty($section_blocks['languages'])) {
            $langs = preg_split('/,|\n|\/|\(|\)|\s{2,}/', implode(' ', $section_blocks['languages']));
            foreach ($langs as $lang) {
                $lang = trim($lang);
                if ($lang && strlen($lang) < 30 && !preg_match($noise_patterns, $lang)) {
                    $langs_found[] = $lang;
                }
            }
        }
        $data['languages'] = array_values(array_unique($langs_found));

        // Education
        $data['education'] = [];
        if (!empty($section_blocks['education'])) {
            $edu_lines = $section_blocks['education'];
            $edu_entry = ['degree' => '', 'institution' => '', 'year' => ''];
            foreach ($edu_lines as $line) {
                if (preg_match('/(?P<degree>Bachelor|BSc|MSc|Master|PhD|Diploma|HSC|SSC|High School|Associate|BA|BS|MA|MS|MBA|Doctor|Certificate)[^,\n]*,?\s*(?P<institution>[^,\d\n]+)?[,\s]*(?P<year>\d{4})?/i', $line, $m)) {
                    $edu_entry = [
                        'degree' => trim($m['degree'] ?? ''),
                        'institution' => trim($m['institution'] ?? ''),
                        'year' => trim($m['year'] ?? '')
                    ];
                    $data['education'][] = $edu_entry;
                    $edu_entry = ['degree' => '', 'institution' => '', 'year' => ''];
                } else {
                    if (preg_match('/\b(\d{4})\b/', $line, $m)) {
                        $edu_entry['year'] = $m[1];
                    }
                    if (preg_match('/(Bachelor|BSc|MSc|Master|PhD|Diploma|HSC|SSC|High School|Associate|BA|BS|MA|MS|MBA|Doctor|Certificate)/i', $line, $m)) {
                        $edu_entry['degree'] = $m[1];
                    } elseif (trim($line) !== '') {
                        $edu_entry['institution'] = trim($line);
                    }
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
            if ($exp_entry['job_title'] || $exp_entry['company'] || $exp_entry['duration'] || $desc_buffer) {
                $exp_entry['description'] = trim($desc_buffer);
                $data['experience'][] = $exp_entry;
            }
        }

        // Filter noise from all arrays
        foreach (["skills", "certifications", "languages"] as $arrKey) {
            $data[$arrKey] = array_values(array_filter($data[$arrKey], function($v) use ($noise_patterns) {
                return $v && !preg_match($noise_patterns, $v);
            }));
        }
        // Remove duplicates and trim whitespace in all arrays
        foreach (["skills", "certifications", "languages"] as $arrKey) {
            $data[$arrKey] = array_values(array_unique(array_map('trim', $data[$arrKey])));
        }

        return $data;
    }
} 