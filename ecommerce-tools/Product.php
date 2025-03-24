class Product
{
    // ...existing code...

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['default' => null])]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'json', nullable: true, options: ['default' => null])]
    private ?array $aiGeneratedContent = null;

    #[ORM\Column(type: 'json', nullable: true, options: ['default' => null])]
    private ?array $originalData = null;

    // ...existing code...
}
