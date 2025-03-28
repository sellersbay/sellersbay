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
        // Determine if we're using premium features (extra prompts & enhanced prompts)
        $usePremiumFeatures = !empty($options) && (!isset($options['isPremiumProcessing']) || count($options) > 1);
        
        // Determine if we're using the premium AI model (GPT-4 Turbo vs GPT-3.5 Turbo)
        $isPremiumProcessing = $options['isPremiumProcessing'] ?? !empty($options);
        
        $prompts = $this->generatePrompts($product, $usePremiumFeatures, $options);
        $results = [];

        foreach ($prompts as $type => $prompt) {
            $results[$type] = $this->callOpenAI($prompt, $isPremiumProcessing, $usePremiumFeatures, $options);
        }

        return $results;
    }

    private function generatePrompts(Product $product, bool $isPremium = false, array $options = []): array
    {
        // Generate target keyphrase first as it will be used in meta title
        $targetKeyphrase = [
            'targetKeyphrase' => $this->createTargetKeyphrasePrompt($product, $isPremium, $options)
        ];
        
        // Standard content fields
        $baseParts = [
            'description' => $this->createDescriptionPrompt($product, $isPremium, $options),
            'shortDescription' => $this->createShortDescriptionPrompt($product, $isPremium, $options),
            'metaDescription' => $this->createMetaDescriptionPrompt($product, $isPremium, $options),
            'imageAltText' => $this->createImageAltTextPrompt($product, $isPremium, $options),
        ];
        
        // Meta title will be generated after target keyphrase
        $metaTitle = [
            'metaTitle' => $this->createMetaTitlePrompt($product, $isPremium, $options)
        ];
        
        // Add premium-only content types
        if ($isPremium) {
            $premiumParts = [
                'seoKeywords' => $this->createSeoKeywordsPrompt($product, $options),
                'socialMediaPost' => $this->createSocialMediaPrompt($product, $options)
            ];
            
            // Combine all parts in the correct order
            return array_merge($targetKeyphrase, $metaTitle, $baseParts, $premiumParts);
        }
        
        // Non-premium just gets the basic fields plus the new required fields
        return array_merge($targetKeyphrase, $metaTitle, $baseParts);
    }

    /**
     * Generate a focused SEO target keyphrase (max 3 words)
     */
    private function createTargetKeyphrasePrompt(Product $product, bool $isPremium = false, array $options = []): string
    {
        $premiumAddition = '';
        if ($isPremium) {
            $targetKeywords = $options['targetKeywords'] ?? '';
            $premiumAddition = <<<PREMIUM

Premium Requirements:
- Consider search volume and competition data
- Incorporate user target keywords if provided: {$targetKeywords}
- Balance specificity with search volume
PREMIUM;
        }
        
        return <<<PROMPT
Generate a focused target keyphrase for SEO purposes:

Product Name: {$product->getName()}
Product Description: {$product->getDescription()}

Requirements:
- Maximum 3 words only
- Must be highly relevant to the product
- Should reflect the main search term someone would use to find this product
- Must be specific enough to target the right audience
- Consider search intent and user behavior
- Prioritize commercial/transactional keywords
- Choose terms with adequate search volume
- Focus on the core function or benefit of the product
{$premiumAddition}

Target Keyphrase:
PROMPT;
    }

    /**
     * Generate a meta title using the target keyphrase, followed by a call to action
     */
    private function createMetaTitlePrompt(Product $product, bool $isPremium = false, array $options = []): string
    {
        $premiumAddition = '';
        if ($isPremium) {
            $targetKeywords = $options['targetKeywords'] ?? '';
            $tone = $options['tone'] ?? 'professional';
            $premiumAddition = <<<PREMIUM

Premium Requirements:
- Incorporate user target keywords if appropriate: {$targetKeywords}
- Use advanced CTR optimization techniques
- Match tone of voice: {$tone}
- A/B test ready (provide slight variation in parentheses)
PREMIUM;
        }
        
        return <<<PROMPT
Generate an SEO-optimized meta title for the following product:

Product Name: {$product->getName()}
Product Description: {$product->getDescription()}

Requirements:
- Format: [Target Keyphrase] | [Call to Action]
- Examples of good call to actions: "Buy Now", "Shop Online", "View Options", etc.
- Target length: 50-60 characters (maximum 60)
- Must be attention-grabbing for search engines
- Include power words that drive clicks
- Make it compelling and relevant
- Front-load with the most important keywords
- Avoid clickbait tactics while maximizing CTR
- Be clear about what the page offers
{$premiumAddition}

Meta Title:
PROMPT;
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
- Write in a professional, engaging tone in a human-like manner
- Do not include the target keywords more than 3 times
- Include the target keywords in the first 100 words
- Use natural language that converts
- Format text with html tags for headings, lists, etc.
- User <h2></h2> first heading for the product name
- Use <h3></h3> for main sections
- Important: Do not use quotation marks around Image Alt Text or Meta Description
- Include a <h4></h4> for the FAQ section
- Break into manageable sections with subheadings
- Important: Optimize product description for AI and voice search
- Use structured data-friendly formatting
- Do not add a <h1></h1> tag
- Include <p><stong>Features:</strong></p> with a bullet list of main features under it
- Highlight unique selling points 
- Focus on benefits to the customer
- Use active voice
- Be compelling and actionable
- Break up paragraphs appropriately 
- Include a call to action
- Ensure readability score above 70
- Optimize for search engines but don't use keyword stuffing
- Ensures no more than 20% of sentences are over 20 words
- Use natural language that converts
- Passive voice should not exceed 10%
- descriptions should incorporate more transition words to enhance flow and coherence 
- Descriptions are detailed and tailored for e-commerce 
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
- Optimize for emotional impact and conversion
- Use psychological triggers
- Match the tone: {$tone}
- Create a single, high-impact description without formatting or quotes
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
- IMPORTANT: Provide a single, clean description with no formatting (no 'A:' or 'B:' prefixes, no quotation marks)
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

    private function callOpenAI(string $prompt, bool $isPremiumProcessing = false, bool $usePremiumFeatures = false, array $options = []): string
    {
        // Set model based on processing mode flag
        $model = $isPremiumProcessing ? 'gpt-4-turbo-preview' : 'gpt-3.5-turbo';
        
        // Set parameters based on premium features
        $temperature = $usePremiumFeatures ? 0.6 : 0.7; // Lower temperature for more focused outputs
        $maxTokens = $usePremiumFeatures ? 1500 : 1000; // More detailed responses for premium features
        
        // Enhanced system prompt for premium features
        $systemPrompt = $usePremiumFeatures 
            ? 'You are an elite e-commerce copywriter and SEO specialist with expertise in conversion optimization. Your content consistently ranks #1 in search results and achieves conversion rates 30% above industry average. Create content that outperforms competitors by addressing user intent perfectly while maintaining SEO best practices. Never use A/B test formatting in your responses.'
            : 'You are an expert e-commerce copywriter and SEO specialist. Your task is to generate optimized content that drives conversions while maintaining SEO best practices. Never use A/B test formatting in your responses.';
        
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
        $content = $data['choices'][0]['message']['content'] ?? '';
        
        // Process the short description to remove A/B formatting if it exists
        if (strpos($prompt, 'Short Description:') !== false) {
            // Replace A/B test format patterns
            $content = preg_replace('/^A:\s*\"?(.*?)\"?\s*B:\s*\"?(.*?)\"?$/ms', '$1', $content);
            $content = preg_replace('/^Option A:\s*\"?(.*?)\"?\s*Option B:\s*\"?(.*?)\"?$/ms', '$1', $content);
            $content = preg_replace('/^Alternative 1:\s*\"?(.*?)\"?\s*Alternative 2:\s*\"?(.*?)\"?$/ms', '$1', $content);
            
            // Remove any remaining quotation marks
            $content = str_replace('"', '', $content);
            $content = str_replace("'", '', $content);
            
            // Trim whitespace
            $content = trim($content);
        }
        
        return $content;
    }
}