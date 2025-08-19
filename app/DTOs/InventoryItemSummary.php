<?php

namespace App\DTOs;

final class InventoryItemSummary
{
    public function __construct(
        public int|string $typeId,   // معرف النوع
        public string $typeName,     // اسم النوع
        public float $inQty,         // داخِل
        public float $outQty,        // خارِج
        public float $availableQty   // متاح = داخل - خارج
    ) {}
}
