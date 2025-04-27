<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BadWordFilterService
{
    private HttpClientInterface $client;
    private string $apiKey;
    
    public function __construct(string $apiKey = null)
    {
        $this->client = HttpClient::create();
        // For PurgoMalum, we don't need an API key
        $this->apiKey = $apiKey ?: '';
    }
    
    /**
     * Check if text contains bad words using PurgoMalum API
     * 
     * @param string $text Text to check
     * @return array Results with status and filtered text if needed
     */
    public function containsBadWords(string $text): array
    {
        try {
            // For demo we're using the free PurgoMalum API
            // In production, consider using more robust paid APIs like:
            // - WebPurify
            // - Sightengine
            // - CleanText
            $response = $this->client->request('GET', 'https://www.purgomalum.com/service/containsprofanity', [
                'query' => [
                    'text' => $text,
                ]
            ]);
            
            $result = $response->getContent();
            
            if ($result === 'true') {
                // Get the filtered version to show what was filtered
                $filteredResponse = $this->client->request('GET', 'https://www.purgomalum.com/service/json', [
                    'query' => [
                        'text' => $text,
                    ]
                ]);
                
                $filteredData = json_decode($filteredResponse->getContent(), true);
                
                return [
                    'containsBadWords' => true,
                    'original' => $text,
                    'filtered' => $filteredData['result'] ?? $text,
                    'message' => 'Inappropriate content detected.'
                ];
            }
            
            return [
                'containsBadWords' => false,
                'original' => $text,
                'filtered' => $text,
                'message' => 'No inappropriate content detected.'
            ];
        } catch (\Exception $e) {
            // If API fails, log the error but allow the text through
            // In production, you might want different fallback behavior
            return [
                'containsBadWords' => false,
                'original' => $text,
                'filtered' => $text,
                'message' => 'API check failed, proceeding with caution.',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Filter bad words from text
     * 
     * @param string $text Text to filter
     * @param string $replacement Character to replace bad words with
     * @return string Filtered text
     */
    public function filterText(string $text, string $replacement = '*'): string
    {
        try {
            $response = $this->client->request('GET', 'https://www.purgomalum.com/service/plain', [
                'query' => [
                    'text' => $text,
                    'fill_char' => $replacement,
                ]
            ]);
            
            return $response->getContent();
        } catch (\Exception $e) {
            // If API fails, return original text
            return $text;
        }
    }
} 