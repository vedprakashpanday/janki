<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'account_name' => trim($this->account_name),
            'account_no' => $this->account_no,
            'account_type' => $this->account_type,
            'bank_name' => $this->bank_name,
            'branch' => $this->branch,
            'ifsc_code' => $this->ifsc_code,
            'status' => $this->status,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}
?>