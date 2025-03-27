<?php

namespace App\Message;

class ProcessProductMessage
{
    private int $productId;
    private int $userId;
    private array $options;

    public function __construct(int $productId, int $userId, array $options = [])
    {
        $this->productId = $productId;
        $this->userId = $userId;
        $this->options = $options;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
} 