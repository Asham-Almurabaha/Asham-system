<?php

namespace App\DTOs\CustomerDetails;

class CustomerBasic
{
    public int $id;
    public string $name;
    public ?string $phone;
    public ?string $email;
    public ?string $national_id;
    public ?string $address;

    public function __construct(
        int $id,
        string $name,
        ?string $phone = null,
        ?string $email = null,
        ?string $national_id = null,
        ?string $address = null
    ) {
        $this->id = $id; $this->name = $name;
        $this->phone = $phone; $this->email = $email;
        $this->national_id = $national_id; $this->address = $address;
    }

    public function toArray(): array
    {
        return [
            'id'=>$this->id,'name'=>$this->name,'phone'=>$this->phone,'email'=>$this->email,
            'national_id'=>$this->national_id,'address'=>$this->address,
        ];
    }
}
