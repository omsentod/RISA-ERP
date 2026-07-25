<?php

namespace App\Domain\Import\Data;

class ProductImportRow
{
    public const STATUS_NEW = 'new';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_INVALID = 'invalid';

    public function __construct(
        public readonly int $sheetIndex,
        public readonly int $rowNumber,
        public readonly string $categoryName,
        public readonly bool $isLocking,
        public readonly ?string $code,
        public readonly ?string $name,
        public readonly ?string $specification,
        public readonly ?string $nieNumber,
        public readonly string $status,
        public readonly ?string $errorReason = null,
        public readonly ?array $existingData = null,
    ) {}

    public function toArray(): array
    {
        return [
            'sheet_index' => $this->sheetIndex,
            'row_number' => $this->rowNumber,
            'category_name' => $this->categoryName,
            'is_locking' => $this->isLocking,
            'code' => $this->code,
            'name' => $this->name,
            'specification' => $this->specification,
            'nie_number' => $this->nieNumber,
            'status' => $this->status,
            'error_reason' => $this->errorReason,
            'existing_data' => $this->existingData,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            sheetIndex: $data['sheet_index'],
            rowNumber: $data['row_number'],
            categoryName: $data['category_name'],
            isLocking: $data['is_locking'],
            code: $data['code'],
            name: $data['name'],
            specification: $data['specification'],
            nieNumber: $data['nie_number'],
            status: $data['status'],
            errorReason: $data['error_reason'] ?? null,
            existingData: $data['existing_data'] ?? null,
        );
    }
}
