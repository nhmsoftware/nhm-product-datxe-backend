<?php

declare(strict_types=1);

namespace App\Modules\Finance\Model;

use App\Core\Traits\HasBigIntId;
use App\Modules\Finance\Model\Enums\VoucherDiscountType;
use App\Modules\Finance\Model\Enums\VoucherServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model quản lý Voucher (Mã giảm giá).
 *
 * @property int $id
 * @property string $code
 * @property string|null $name
 * @property VoucherServiceType $service_type
 * @property VoucherDiscountType $discount_type
 * @property float $discount_value
 * @property float $min_order_amount
 * @property float|null $max_discount_amount
 * @property \Illuminate\Support\Carbon $valid_from
 * @property \Illuminate\Support\Carbon $valid_until
 * @property int|null $total_usage_limit
 * @property int $used_count
 * @property bool $is_active
 * @property string|null $description
 */
final class Voucher extends Model
{
    use SoftDeletes, HasBigIntId;

    protected $table = 'vouchers';

    protected $fillable = [
        'code',
        'name',
        'service_type',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'valid_from',
        'valid_until',
        'total_usage_limit',
        'used_count',
        'is_active',
        'description',
    ];

    protected $casts = [
        'service_type' => VoucherServiceType::class,
        'discount_type' => VoucherDiscountType::class,
        'discount_value' => 'float',
        'min_order_amount' => 'float',
        'max_discount_amount' => 'float',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'used_count' => 'integer',
        'total_usage_limit' => 'integer',
    ];

    /**
     * Kiểm tra voucher còn hạn dùng hay không.
     */
    public function isValid(): bool
    {
        $now = now();

        if (!$this->is_active) {
            return false;
        }

        if ($now->lt($this->valid_from) || $now->gt($this->valid_until)) {
            return false;
        }

        if ($this->total_usage_limit !== null && $this->used_count >= $this->total_usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Kiểm tra xem voucher có hết hạn hay chưa (chỉ tính thời gian).
     */
    public function isExpired(): bool
    {
        return now()->gt($this->valid_until) || ($this->total_usage_limit !== null && $this->used_count >= $this->total_usage_limit);
    }

    /**
     * Tính toán số tiền giảm giá dựa trên tổng tiền đơn hàng.
     */
    public function calculateDiscount(float $orderAmount): float
    {
        if ($orderAmount < $this->min_order_amount) {
            return 0.0;
        }

        $discount = 0.0;
        if ($this->discount_type === VoucherDiscountType::FIXED) {
            $discount = $this->discount_value;
        } elseif ($this->discount_type === VoucherDiscountType::PERCENT) {
            $discount = ($orderAmount * $this->discount_value) / 100;
            if ($this->max_discount_amount !== null) {
                $discount = min($discount, $this->max_discount_amount);
            }
        }

        return min($discount, $orderAmount);
    }
}
