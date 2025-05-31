<?php
namespace Sohagsrz\ResumeParser;

use Smalot\PdfParser\Parser;

class OpenAIResumeParser
{
    public static function parse($pdf_path, $openai_api_key)
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($pdf_path);
        $text = $pdf->getText();

        $prompt = "Extract the following structured JSON from this resume text. If a field is missing, leave it empty.\n\n" .
            "{\n  \"name\": \"\",\n  \"email\": \"\",\n  \"phone\": \"\",\n  \"address\": \"\",\n  \"linkedin\": [],\n  \"github\": [],\n  \"twitter\": [],\n  \"facebook\": [],\n  \"instagram\": [],\n  \"stackoverflow\": [],\n  \"dribbble\": [],\n  \"behance\": [],\n  \"medium\": [],\n  \"youtube\": [],\n  \"tiktok\": [],\n  \"pinterest\": [],\n  \"telegram\": [],\n  \"whatsapp\": [],\n  \"blog\": [],\n  \"website\": [],\n  \"skills\": [],\n  \"education\": [ { \"degree\": \"\", \"institution\": \"\", \"year\": \"\" } ],\n  \"experience\": [ { \"job_title\": \"\", \"company\": \"\", \"duration\": \"\", \"description\": \"\" } ],\n  \"certifications\": [],\n  \"languages\": []\n}\n\nResume text:\n" . $text . "\n\nReturn only the JSON.";

        $response = self::callOpenAI($prompt, $openai_api_key);
        if (!$response) {
            return ["error" => "OpenAI API call failed."];
        }
        $json = self::extractJson($response);
        if (!$json) {
            return ["error" => "No valid JSON found in OpenAI response.", "raw_response" => $response];
        }
        return json_decode($json, true);
    }

    private static function callOpenAI($prompt, $api_key)
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 1500,
            'temperature' => 0.2
        ];
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        if (!$result) return null;
        $resultArr = json_decode($result, true);
        if (isset($resultArr['choices'][0]['message']['content'])) {
            return $resultArr['choices'][0]['message']['content'];
        }
        return null;
    }

    private static function extractJson($text)
    {
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            return $matches[0];
        }
        return null;
    }
} 