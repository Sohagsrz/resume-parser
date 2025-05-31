# 🚀 Resume Parser

[![Packagist](https://img.shields.io/packagist/v/sohagsrz/resume-parser?color=red)](https://packagist.org/packages/sohagsrz/resume-parser)
[![License](https://img.shields.io/github/license/sohagsrz/resume-parser?color=green)](LICENSE)
[![GitHub stars](https://img.shields.io/github/stars/sohagsrz/resume-parser?style=social)](https://github.com/sohagsrz/resume-parser/stargazers)

> **A modern PHP package to extract structured data from resume PDFs — with both classic and OpenAI-powered AI parsing!**

Easily extract names, emails, phone numbers, social handles, skills, education, experience, certifications, and languages from any resume PDF. Use classic parsing for speed, or unleash the power of OpenAI for even more robust results.

---

## ✨ Features

- Extracts name, email, phone, address
- Detects and normalizes global phone numbers
- Extracts all major social handles (LinkedIn, GitHub, Twitter/X, Facebook, Instagram, Stack Overflow, Dribbble, Behance, Medium, YouTube, TikTok, Pinterest, Telegram, WhatsApp, blog, website)
- Parses skills (technical, soft, and unique skills)
- Extracts education, experience, certifications, and languages
- Section-based parsing for high accuracy
- Optional: AI-powered parsing using OpenAI for even more robust extraction
- Outputs structured JSON/array

---

## 📦 Installation

```bash
composer require sohagsrz/resume-parser
```

---

## ⚡ Usage

### Manual/Classic Parsing

```php
require 'vendor/autoload.php';
use Sohagsrz\ResumeParser\ResumeParser;

$result = ResumeParser::parse('path/to/resume.pdf');
echo json_encode($result, JSON_PRETTY_PRINT);
```

### AI-Powered Parsing (OpenAI)

```php
require 'vendor/autoload.php';
use Sohagsrz\ResumeParser\OpenAIResumeParser;

$apiKey = 'sk-...'; // Your OpenAI API key
$result = OpenAIResumeParser::parse('path/to/resume.pdf', $apiKey);
echo json_encode($result, JSON_PRETTY_PRINT);
```

---

## 📝 Example Output Structure

```json
{
  "name": "Md Sohag Islam",
  "email": "mdsohagislam25@gmail.com",
  "phone": "+8801798965122",
  "address": "Niyamotpur, Saidpur, Bangladesh",
  "linkedin": ["https://linkedin.com/in/sohagbd"],
  "github": ["https://github.com/sohag-dev"],
  "twitter": [],
  "facebook": [],
  "instagram": [],
  "stackoverflow": [],
  "dribbble": [],
  "behance": [],
  "medium": [],
  "youtube": [],
  "tiktok": [],
  "pinterest": [],
  "telegram": [],
  "whatsapp": [],
  "blog": [],
  "website": [],
  "skills": ["PHP", "Laravel", "React", "Tailwind CSS"],
  "education": [
    {
      "degree": "BSc in Computer Science",
      "institution": "AIUB",
      "year": "2026"
    }
  ],
  "experience": [
    {
      "job_title": "Backend Developer",
      "company": "Ujjol Lab",
      "duration": "2022-2024",
      "description": "Developed REST APIs, maintained Laravel applications."
    }
  ],
  "certifications": ["AWS Cloud Practitioner", "Meta Frontend Certificate"],
  "languages": ["English", "Bangla"]
}
```

---

## 💻 Example Demos

- `example/parse_example.php` — Manual/classic parsing
- `example/bootstrap_upload.php` — Manual/classic parsing with Bootstrap upload form
- `example/bootstrap_ai_upload.php` — AI-powered parsing with Bootstrap upload form and OpenAI API key

---

## 🔑 OpenAI API Key

To use the AI-powered parser, you need an OpenAI API key. You can get one from [OpenAI](https://platform.openai.com/account/api-keys). **Keep your API key secure and do not expose it publicly.**

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome! Feel free to [open an issue](https://github.com/sohagsrz/resume-parser/issues) or submit a pull request.

If you like this project, please ⭐ star it and [follow me](https://github.com/sohagsrz) for more cool open-source tools!

---

## 📄 License

MIT

## 👤 Author

- [sohagsrz](https://github.com/sohagsrz)
