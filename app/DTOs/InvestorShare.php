<?php

namespace App\DTOs;

/**
 * @psalm-type InvestorShareArray=array{
 *   id:int,
 *   share_percentage:float,
 *   share_value?:float|null
 * }
 */
final class InvestorShare
{
    public int $id;
    public float $sharePercentage;
    public ?float $shareValue;

    /**
     * @param int         $id
     * @param float       $sharePercentage
     * @param float|null  $shareValue
     */
    public function __construct(int $id, float $sharePercentage, ?float $shareValue = null)
    {
        $this->id = $id;
        $this->sharePercentage = $sharePercentage;
        $this->shareValue = $shareValue;
    }

    /**
     * @param array{id: mixed, share_percentage: mixed, share_value?: mixed} $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            (int) $row['id'],
            (float) $row['share_percentage'],
            array_key_exists('share_value', $row) && $row['share_value'] !== null && $row['share_value'] !== ''
                ? (float) $row['share_value']
                : null
        );
    }
}
