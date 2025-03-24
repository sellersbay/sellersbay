<?php

namespace App\Service;

use App\Entity\Product;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIService
{
    private HttpClientInterface $httpClient;
    private string $openaiApiKey;

    public function __construct(
        HttpClientInterface $httpClient,
        string $openaiApiKey
    ) {
        $this->httpClient = $httpClient;
        $this->openaiApiKey = $openaiApiKey;
    }

    public function generateContent(Product $product, array $options = []): array
    {
        $isPremium = !empty($options);
        $prompts = $this->generatePrompts($product, $isPremium, $options);
        $results = [];

        foreach ($prompts as $type => $prompt) {
            $results[$type] = $this->callOpenAI($prompt, $isPremium, $options);
        }

        return $results;
    }

    private function generatePrompts(Product $product, bool $isPremium = false, array $options = []): array
    {
        $baseParts = [
            'description' => $this->createDescriptionPrompt($product, $isPremium, $options),
            'shortDescription' => $this->createShortDescriptionPrompt($product, $isPremium, $options),
            'metaDescription' => $this->createMetaDescriptionPrompt($product, $isPremium, $options),
            'imageAltText' => $this->createImageAltTextPrompt($product, $isPremium, $options),
        ];
        
        // Add premium-only content types
        if ($isPremium) {
            $baseParts['seoKeywords'] = $this->createSeoKeywordsPrompt($product, $options);
            $baseParts['socialMediaPost'] = $this->createSocialMediaPrompt($product, $options);
        }
        
        return $baseParts;
    }

    private function createDescriptionPrompt(Product $product, bool $isPremium = false, array $options = []): string
    {
        $targetKeywords = $options['targetKeywords'] ?? '';
        $competitorUrls = $options['competitorUrls'] ?? '';
        $tone = $options['tone'] ?? 'professional';
        
        $premiumAddition = '';
        if ($isPremium) {
            $premiumAddition = <<<PREMIUM

Additional Premium Requirements:
- Target these specific keywords: {$targetKeywords}
- Analyze and outperform content from these competitor URLs: {$competitorUrls}
- Use this specific tone of voice: {$tone}
- Create content that ranks higher by addressing user intent
- Include FAQ section with 3 common questions
- Add structured data-friendly formatting
- Ensure readability score above 70
PREMIUM;
        }
        
        return <<<PROMPT
Generate a detailed, SEO-optimized product description for the following product:

Product Name: {$product->getName()}
Original Description: {$product->getDescription()}

Requirements:
- Write in a professional, engaging tone
- Include key features and benefits
- Optimize for search engines
- Use natural language that converts
- Keep it between 300-500 words
- Include relevant keywords naturally
- Focus on value proposition
- Use proper formatting with paragraphs
{$premiumAddition}

Product Description:
PROMPT;
    }

    private function createShortDescriptionPrompt(Product $product, bool $isPremium = false, array $options = []): string
    {
        $premiumAddition = '';
        if ($isPremium) {
            $tone = $options['tone'] ?? 'professional';
            $premiumAddition = <<<PREMIUM

Premium Requirements:
- A/B test ready (provide two alternatives)
- Optimize for emotional impact and conversion
- Use psychological triggers
- Match the tone: {$tone}
PREMIUM;
        }
        
        return <<<PROMPT
Create a concise, compelling short description for the following product:

Product Name: {$product->getName()}
Original Description: {$product->getDescription()}

Requirements:
- Maximum 150 characters
- Highlight key selling points
- Include main keyword
- Focus on benefits
- Use active voice
- Make it attention-grabbing
{$premiumAddition}

Short Description:
PROMPT;
    }

    private function createMetaDescriptionPrompt(Product $product, bool $isPremium = false, array $options = []): string
    {
        $premiumAddition = '';
        if ($isPremium) {
            $targetKeywords = $options['targetKeywords'] ?? '';
            $premiumAddition = <<<PREMIUM

Premium Requirements:
- Target these specific SEO keywords: {$targetKeywords}
- Create a meta description with optimal CTR potential
- Include micro-formatting if appropriate
- Use power words and emotional triggers
PREMIUM;
        }
        
        return <<<PROMPT
Generate an SEO-optimized meta description for the following product:

Product Name: {$product->getName()}
Original Description: {$product->getDescription()}

Requirements:
- Maximum 155 characters
- Include primary keyword
- Be compelling and actionable
- Use active voice
- Include a call to action
- Focus on unique value proposition
{$premiumAddition}

Meta Description:
PROMPT;
    }

    private function createImageAltTextPrompt(Product $product, bool $isPremium = false, array $options = []): string
    {
        $premiumAddition = '';
        if ($isPremium) {
            $premiumAddition = <<<PREMIUM

Premium Requirements:
- Optimize for image search rankings
- Balance SEO with accessibility perfectly
- Include context of where the image appears on the page
PREMIUM;
        }
        
        return <<<PROMPT
Create an SEO-friendly alt text for the product image:

Product Name: {$product->getName()}
Product Type: {$product->getDescription()}

Requirements:
- Be descriptive but concise
- Include product name
- Maximum 125 characters
- Be specific about the product
- Include relevant keywords naturally
- Focus on accessibility
{$premiumAddition}

Alt Text:
PROMPT;
    }

    /**
     * Premium-only prompt for SEO keywords extraction
     */
    private function createSeoKeywordsPrompt(Product $product, array $options = []): string
    {
        $targetKeywords = $options['targetKeywords'] ?? '';
        
        return <<<PROMPT
Generate a comprehensive list of SEO keywords for this product:

Product Name: {$product->getName()}
Product Description: {$product->getDescription()}
Target Keywords: {$targetKeywords}

Requirements:
- Provide 15-20 relevant keywords and phrases
- Include long-tail variations
- Group by search intent (informational, transactional, etc.)
- Include search volume estimates
- Sort by priority (high to low)
- Include LSI (Latent Semantic Indexing) keywords
- Consider keyword difficulty

SEO Keywords:
PROMPT;
    }
    
    /**
     * Premium-only prompt for social media post creation
     */
    private function createSocialMediaPrompt(Product $product, array $options = []): string
    {
        $tone = $options['tone'] ?? 'professional';
        $platform = $options['platform'] ?? 'Instagram';
        
        return <<<PROMPT
Create an engaging social media post to promote this product:

Product Name: {$product->getName()}
Product Description: {$product->getDescription()}
Platform: {$platform}
Tone: {$tone}

Requirements:
- Create a compelling post optimized for {$platform}
- Include appropriate hashtags
- Keep within platform's ideal character count
- Include a strong call to action
- Use engaging, shareable language
- Incorporate emotional triggers
- Highlight key benefits

Social Media Post:
PROMPT;
    }

    private function callOpenAI(string $prompt, bool $isPremium = false, array $options = []): string
    {
        // Set model and parameters based on premium status
        $model = $isPremium ? 'gpt-4-turbo-preview' : 'gpt-3.5-turbo'; 
        $temperature = $isPremium ? 0.6 : 0.7; // Lower temperature for more focused outputs
        $maxTokens = $isPremium ? 1500 : 1000; // More detailed responses for premium
        
        // Enhanced system prompt for premium users
        $systemPrompt = $isPremium 
            ? 'You are an elite e-commerce copywriter and SEO specialist with expertise in conversion optimization. Your content consistently ranks #1 in search results and achieves conversion rates 30% above industry average. Create content that outperforms competitors by addressing user intent perfectly while maintaining SEO best practices.'
            : 'You are an expert e-commerce copywriter and SEO specialist. Your task is to generate optimized content that drives conversions while maintaining SEO best practices.';
        
        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ],
        ]);

        $data = $response->toArray();
        return $data['choices'][0]['message']['content'] ?? '';
    }
}