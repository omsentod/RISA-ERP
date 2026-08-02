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
        public readonly ?string $code,
        public readonly ?string $name,
        public readonly ?string $specification,
        public readonly ?string $nieNumber,
        public readonly int $defaultQuantity = 1,
        public readonly ?string $productGroupCode = null,
        public readonly string $status = self::STATUS_NEW,
        public readonly ?string $errorReason = null,
        public readonly ?array $existingData = null,
    ) {}

    public function toArray(): array
    {
        return [
            'sheet_index' => $this->sheetIndex,
            'row_number' => $this->rowNumber,
            'category_name' => $this->categoryName,
            'code' => $this->code,
            'name' => $this->name,
            'specification' => $this->specification,
            'nie_number' => $this->nieNumber,
            'default_quantity' => $this->defaultQuantity,
            'product_group_code' => $this->productGroupCode,
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
            code: $data['code'],
            name: $data['name'],
            specification: $data['specification'],
            nieNumber: $data['nie_number'],
            defaultQuantity: $data['default_quantity'] ?? 1,
            productGroupCode: $data['product_group_code'] ?? null,
            status: $data['status'],
            errorReason: $data['error_reason'] ?? null,
            existingData: $data['existing_data'] ?? null,
        );
    }
}
