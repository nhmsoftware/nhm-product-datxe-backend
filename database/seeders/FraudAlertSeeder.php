<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FraudAlertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $alerts = [
            [
                'target_type' => 2, // DRIVER
                'target_id' => 'DRV-8821',
                'fraud_type' => 1, // FAKE_GPS
                'risk_level' => 4, // CRITICAL
                'status' => 1, // PENDING
                'title' => 'Phát hiện Fake GPS liên tục',
                'description' => 'Tài xế có dấu hiệu sử dụng phần mềm giả lập vị trí tại khu vực Quận 1.',
                'evidence_metadata' => ['device' => 'Android Rooted', 'coordinates' => '10.776, 106.701'],
            ],
            [
                'target_type' => 1, // CUSTOMER
                'target_id' => 'CUST-1029',
                'fraud_type' => 2, // PROMO_ABUSE
                'risk_level' => 3, // HIGH
                'status' => 2, // INVESTIGATING
                'title' => 'Lạm dụng mã khuyến mãi mới',
                'description' => 'Khách hàng tạo nhiều tài khoản trên cùng một thiết bị để dùng mã WELCOME.',
                'evidence_metadata' => ['device_id' => 'D882-X992-ZZ01', 'accounts' => 5],
            ],
            [
                'target_type' => 4, // TRANSACTION
                'target_id' => 'TXN-99021',
                'fraud_type' => 4, // UNUSUAL_TRANSACTION
                'risk_level' => 2, // MEDIUM
                'status' => 3, // RESOLVED
                'title' => 'Giao dịch giá trị lớn bất thường',
                'description' => 'Phát hiện giao dịch nạp tiền 50,000,000đ từ thẻ ngân hàng mới.',
                'evidence_metadata' => ['amount' => 50000000, 'bank' => 'Unknown'],
            ],
            [
                'target_type' => 2, // DRIVER
                'target_id' => 'DRV-7712',
                'fraud_type' => 3, // GHOST_RIDE
                'risk_level' => 3, // HIGH
                'status' => 1, // PENDING
                'title' => 'Nghi vấn chuyến xe ma',
                'description' => 'Chuyến xe kết thúc quá nhanh so với quãng đường thực tế (2km trong 10 giây).',
                'evidence_metadata' => ['distance' => '2km', 'duration' => '10s'],
            ],
        ];

        foreach ($alerts as $alert) {
            \App\Modules\RiskManagement\Model\FraudAlert::create($alert);
        }
    }
}
