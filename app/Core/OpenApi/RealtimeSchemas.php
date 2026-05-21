<?php

declare(strict_types=1);

namespace App\Core\OpenApi;

use OpenApi\Attributes as OA;

/**
 * OpenAPI Schema Components cho Realtime Events
 *
 * Định nghĩa các schema payload của Socket.io / Redis cho team Frontend dễ đọc.
 */
class RealtimeSchemas
{
    #[OA\Schema(
        schema: 'Realtime_RideNewOffer',
        description: 'Payload nhận được khi có cuốc xe mới (ride.new_offer)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.new_offer'),
            new OA\Property(property: 'user_id', type: 'string', description: 'ID tài xế nhận cuốc'),
            new OA\Property(property: 'ride_id', type: 'string', description: 'ID chuyến xe'),
            new OA\Property(property: 'ride_type', type: 'string', example: 'Đi ngay'),
            new OA\Property(property: 'travel_date', type: 'string', nullable: true, example: '2026-05-21'),
            new OA\Property(property: 'travel_time', type: 'string', nullable: true, example: '15:30:00'),
            new OA\Property(property: 'pickup_address', type: 'string', example: '123 Đường ABC'),
            new OA\Property(property: 'destination_address', type: 'string', example: '456 Đường XYZ'),
            new OA\Property(property: 'distance_km', type: 'number', format: 'float', example: 12.5),
            new OA\Property(property: 'total_price', type: 'number', format: 'float', example: 85000),
            new OA\Property(property: 'vehicle_type', type: 'string', example: 'Xe Máy'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function rideNewOffer(): void {}

    #[OA\Schema(
        schema: 'Realtime_RideAccepted',
        description: 'Payload nhận được khi tài xế nhận chuyến (ride.accepted)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.accepted'),
            new OA\Property(property: 'ride_id', type: 'string'),
            new OA\Property(
                property: 'driver',
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'string'),
                    new OA\Property(property: 'full_name', type: 'string'),
                    new OA\Property(property: 'vehicle_name', type: 'string'),
                    new OA\Property(property: 'vehicle_number', type: 'string'),
                    new OA\Property(property: 'vehicle_type', type: 'string'),
                    new OA\Property(property: 'current_lat', type: 'number', format: 'float', example: 10.762622),
                    new OA\Property(property: 'current_lng', type: 'number', format: 'float', example: 106.660172),
                ]
            ),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function rideAccepted(): void {}

    #[OA\Schema(
        schema: 'Realtime_RideRejected',
        description: 'Payload nhận được khi tài xế từ chối chuyến (ride.rejected)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.rejected'),
            new OA\Property(property: 'ride_id', type: 'string'),
            new OA\Property(
                property: 'driver',
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'string'),
                    new OA\Property(property: 'full_name', type: 'string'),
                ]
            ),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function rideRejected(): void {}

    #[OA\Schema(
        schema: 'Realtime_RideArrived',
        description: 'Payload nhận được khi tài xế đã đến điểm đón (ride.arrived)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.arrived'),
            new OA\Property(property: 'ride_id', type: 'string'),
            new OA\Property(
                property: 'driver',
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'string'),
                    new OA\Property(property: 'current_lat', type: 'number', format: 'float', example: 10.762622),
                    new OA\Property(property: 'current_lng', type: 'number', format: 'float', example: 106.660172),
                ]
            ),
            new OA\Property(property: 'message', type: 'string', example: 'Tài xế đã đến điểm đón.'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function rideArrived(): void {}

    #[OA\Schema(
        schema: 'Realtime_RidePickedUp',
        description: 'Payload nhận được khi tài xế xác nhận đã đón khách (ride.picked_up)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.picked_up'),
            new OA\Property(property: 'ride_id', type: 'string'),
            new OA\Property(
                property: 'driver',
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'string'),
                    new OA\Property(property: 'full_name', type: 'string'),
                    new OA\Property(property: 'current_lat', type: 'number', format: 'float', example: 10.762622),
                    new OA\Property(property: 'current_lng', type: 'number', format: 'float', example: 106.660172),
                ]
            ),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function ridePickedUp(): void {}

    #[OA\Schema(
        schema: 'Realtime_RideStarted',
        description: 'Payload nhận được khi chuyến xe bắt đầu di chuyển (ride.started)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.started'),
            new OA\Property(property: 'ride_id', type: 'string'),
            new OA\Property(property: 'driver_id', type: 'string'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function rideStarted(): void {}

    #[OA\Schema(
        schema: 'Realtime_RideCompleted',
        description: 'Payload nhận được khi chuyến xe hoàn thành (ride.completed)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.completed'),
            new OA\Property(property: 'ride_id', type: 'string'),
            new OA\Property(property: 'driver_id', type: 'string'),
            new OA\Property(property: 'total_fare', type: 'number', format: 'float', example: 85000),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function rideCompleted(): void {}

    #[OA\Schema(
        schema: 'Realtime_RideCancelled',
        description: 'Payload nhận được khi chuyến xe bị hủy (ride.cancelled)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.cancelled'),
            new OA\Property(property: 'ride_id', type: 'string'),
            new OA\Property(property: 'user_id', type: 'string', description: 'ID của user nhận event'),
            new OA\Property(property: 'customer_id', type: 'string'),
            new OA\Property(property: 'driver_id', type: 'string', nullable: true),
            new OA\Property(property: 'reason', type: 'string', nullable: true),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function rideCancelled(): void {}

    #[OA\Schema(
        schema: 'Realtime_DriverStatusUpdated',
        description: 'Payload nhận được khi trạng thái tài xế thay đổi (driver.status.updated)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'driver.status.updated'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'status', type: 'string', example: 'active'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function driverStatusUpdated(): void {}

    #[OA\Schema(
        schema: 'Realtime_LocationUpdated',
        description: 'Payload vị trí GPS (qua Redis Tracking / Socket.io trực tiếp) (tracking.location.updated)',
        properties: [
            new OA\Property(property: 'ride_id', type: 'string', nullable: true),
            new OA\Property(property: 'user_id', type: 'string', description: 'Dành cho redis pubsub'),
            new OA\Property(property: 'role', type: 'integer', description: 'Dành cho redis pubsub'),
            new OA\Property(
                property: 'location',
                type: 'object',
                properties: [
                    new OA\Property(property: 'lat', type: 'number', format: 'float', example: 10.762622),
                    new OA\Property(property: 'lng', type: 'number', format: 'float', example: 106.660172),
                    new OA\Property(property: 'tracked_at', type: 'string', format: 'date-time')
                ]
            ),
            new OA\Property(property: 'heading', type: 'number', description: 'Góc quay của xe (chỉ có trong socket.io direct)', example: 90)
        ]
    )]
    public static function locationUpdated(): void {}

    #[OA\Schema(
        schema: 'Realtime_NotificationCreated',
        description: 'Payload khi có thông báo mới (notification.created)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'notification.created'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(
                property: 'notification',
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'string'),
                    new OA\Property(property: 'type', type: 'string'),
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'icon', type: 'string'),
                    new OA\Property(property: 'category', type: 'string', example: 'system'),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                ]
            ),
            new OA\Property(property: 'unread_count', type: 'integer', example: 5),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function notificationCreated(): void {}

    #[OA\Schema(
        schema: 'Realtime_DriverApplicationRejected',
        description: 'Payload nhận được khi hồ sơ tài xế bị từ chối (driver.application_rejected)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'driver.application_rejected'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'reason', type: 'string', example: 'Thiếu giấy tờ'),
            new OA\Property(property: 'message', type: 'string', example: 'Rất tiếc! Hồ sơ tài xế của bạn đã bị từ chối.'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function driverApplicationRejected(): void {}

    #[OA\Schema(
        schema: 'Realtime_DriverApplicationApproved',
        description: 'Payload nhận được khi hồ sơ tài xế được duyệt (driver.application_approved)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'driver.application_approved'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'application_id', type: 'string'),
            new OA\Property(property: 'message', type: 'string', example: 'Chúc mừng! Hồ sơ tài xế của bạn đã được duyệt.'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function driverApplicationApproved(): void {}

    #[OA\Schema(
        schema: 'Realtime_UserStatusUpdated',
        description: 'Payload nhận được khi trạng thái người dùng thay đổi (user.status_updated)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'user.status_updated'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'is_active', type: 'boolean', example: false),
            new OA\Property(property: 'lock_reason', type: 'string', nullable: true, example: 'Vi phạm chính sách'),
            new OA\Property(property: 'lock_expired_at', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'message', type: 'string'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function userStatusUpdated(): void {}

    #[OA\Schema(
        schema: 'Realtime_UserWarned',
        description: 'Payload nhận được khi người dùng bị cảnh cáo (user.warned)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'user.warned'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'violation_id', type: 'string'),
            new OA\Property(property: 'type', type: 'string'),
            new OA\Property(property: 'reason', type: 'string'),
            new OA\Property(property: 'violation_count', type: 'integer', example: 1),
            new OA\Property(property: 'message', type: 'string'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function userWarned(): void {}

    #[OA\Schema(
        schema: 'Realtime_RideScheduledPushedToPool',
        description: 'Payload nhận được khi chuyến xe đặt trước được đẩy vào pool (ride.scheduled_pushed_to_pool)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.scheduled_pushed_to_pool'),
            new OA\Property(property: 'ride_ids', type: 'array', items: new OA\Items(type: 'string')),
            new OA\Property(property: 'user_id', type: 'string', example: 'all_drivers'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function rideScheduledPushedToPool(): void {}

    #[OA\Schema(
        schema: 'Realtime_RideCancellationResponded',
        description: 'Payload nhận được khi yêu cầu hủy chuyến được phản hồi (ride.cancellation_responded)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.cancellation_responded'),
            new OA\Property(property: 'ride_id', type: 'string'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'driver_id', type: 'string'),
            new OA\Property(property: 'customer_id', type: 'string'),
            new OA\Property(property: 'is_approved', type: 'boolean', example: true),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function rideCancellationResponded(): void {}

    #[OA\Schema(
        schema: 'Realtime_RideCancellationRequested',
        description: 'Payload nhận được khi có yêu cầu hủy chuyến (ride.cancellation_requested)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.cancellation_requested'),
            new OA\Property(property: 'ride_id', type: 'string'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'driver_id', type: 'string'),
            new OA\Property(property: 'customer_id', type: 'string'),
            new OA\Property(property: 'reason', type: 'string', example: 'Đợi quá lâu'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function rideCancellationRequested(): void {}

    #[OA\Schema(
        schema: 'Realtime_RideAssignedByAdmin',
        description: 'Payload nhận được khi chuyến xe được gán bởi admin (ride.assigned_by_admin)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.assigned_by_admin'),
            new OA\Property(property: 'ride_id', type: 'string'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'customer_id', type: 'string'),
            new OA\Property(property: 'driver_id', type: 'string'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function rideAssignedByAdmin(): void {}

    #[OA\Schema(
        schema: 'Realtime_RidePickupProofCaptured',
        description: 'Payload nhận được khi tài xế chụp ảnh nhận hàng (ride.pickup_proof_captured)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.pickup_proof_captured'),
            new OA\Property(property: 'ride_id', type: 'string'),
            new OA\Property(property: 'driver_id', type: 'string'),
            new OA\Property(property: 'customer_id', type: 'string'),
            new OA\Property(property: 'photo_url', type: 'string', nullable: true),
            new OA\Property(property: 'is_skipped', type: 'boolean', example: false),
            new OA\Property(property: 'skip_reason', type: 'string', nullable: true),
            new OA\Property(
                property: 'location',
                type: 'object',
                properties: [
                    new OA\Property(property: 'lat', type: 'number', format: 'float', example: 10.762622),
                    new OA\Property(property: 'lng', type: 'number', format: 'float', example: 106.660172),
                ]
            ),
            new OA\Property(property: 'message', type: 'string'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function ridePickupProofCaptured(): void {}

    #[OA\Schema(
        schema: 'Realtime_RideDeliveryProofCaptured',
        description: 'Payload nhận được khi tài xế chụp ảnh giao hàng (ride.delivery_proof_captured)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'ride.delivery_proof_captured'),
            new OA\Property(property: 'ride_id', type: 'string'),
            new OA\Property(property: 'driver_id', type: 'string'),
            new OA\Property(property: 'customer_id', type: 'string'),
            new OA\Property(property: 'photo_url', type: 'string', nullable: true),
            new OA\Property(property: 'is_skipped', type: 'boolean', example: false),
            new OA\Property(property: 'skip_reason', type: 'string', nullable: true),
            new OA\Property(
                property: 'location',
                type: 'object',
                properties: [
                    new OA\Property(property: 'lat', type: 'number', format: 'float', example: 10.762622),
                    new OA\Property(property: 'lng', type: 'number', format: 'float', example: 106.660172),
                ]
            ),
            new OA\Property(property: 'message', type: 'string'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function rideDeliveryProofCaptured(): void {}

    #[OA\Schema(
        schema: 'Realtime_FoodOrderUpdated',
        description: 'Payload nhận được khi trạng thái đơn hàng thay đổi (food_order.updated)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'food_order.updated'),
            new OA\Property(property: 'order_id', type: 'string'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'status', type: 'string'),
            new OA\Property(property: 'message', type: 'string'),
            new OA\Property(property: 'reason', type: 'string', nullable: true),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function foodOrderUpdated(): void {}

    #[OA\Schema(
        schema: 'Realtime_FoodCancellationHandled',
        description: 'Payload nhận được khi yêu cầu hủy đơn hàng được xử lý (food_order.cancellation_handled)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'food_order.cancellation_handled'),
            new OA\Property(property: 'order_id', type: 'string'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'action', type: 'string', example: 'accepted'),
            new OA\Property(property: 'message', type: 'string'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function foodCancellationHandled(): void {}

    #[OA\Schema(
        schema: 'Realtime_MerchantApproved',
        description: 'Payload nhận được khi hồ sơ nhà hàng được duyệt (merchant.approved)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'merchant.approved'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'merchant_id', type: 'string'),
            new OA\Property(property: 'status', type: 'integer', example: 2),
            new OA\Property(property: 'status_label', type: 'string', example: 'Đã duyệt'),
            new OA\Property(property: 'message', type: 'string', example: 'Chúc mừng! Hồ sơ Merchant của bạn đã được duyệt.'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function merchantApproved(): void {}

    #[OA\Schema(
        schema: 'Realtime_NotificationUnreadCountUpdated',
        description: 'Payload nhận được khi số lượng thông báo chưa đọc thay đổi (notification.unread_count_updated)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'notification.unread_count_updated'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'unread_count', type: 'integer', example: 3),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function notificationUnreadCountUpdated(): void {}

    #[OA\Schema(
        schema: 'Realtime_FoodOrderCreated',
        description: 'Payload nhận được khi có đơn hàng mới (food.order_created)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'food.order_created'),
            new OA\Property(property: 'order_id', type: 'string'),
            new OA\Property(property: 'customer_id', type: 'string'),
            new OA\Property(property: 'merchant_id', type: 'string'),
            new OA\Property(property: 'total_price', type: 'number', format: 'float', example: 120000),
            new OA\Property(property: 'message', type: 'string', example: 'Bạn có đơn hàng mới!'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function foodOrderCreated(): void {}

    #[OA\Schema(
        schema: 'Realtime_FinanceRefundProcessed',
        description: 'Payload nhận được khi hoàn tiền được xử lý (finance.refund.processed)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'finance.refund.processed'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'refund_id', type: 'string'),
            new OA\Property(property: 'status', type: 'string', example: 'APPROVED'),
            new OA\Property(property: 'amount', type: 'number', format: 'float', example: 85000),
            new OA\Property(property: 'message', type: 'string'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function financeRefundProcessed(): void {}

    #[OA\Schema(
        schema: 'Realtime_ComplaintHandled',
        description: 'Payload nhận được khi khiếu nại được xử lý (complaint.handled)',
        properties: [
            new OA\Property(property: 'event', type: 'string', example: 'complaint.handled'),
            new OA\Property(property: 'user_id', type: 'string'),
            new OA\Property(property: 'complaint_id', type: 'string'),
            new OA\Property(property: 'action', type: 'string'),
            new OA\Property(property: 'message', type: 'string'),
            new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time')
        ]
    )]
    public static function complaintHandled(): void {}
}
