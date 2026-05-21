<?php

declare(strict_types=1);

namespace App\Core\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Lớp Dummy dùng để render tài liệu các Realtime Events lên giao diện Swagger UI.
 * (Frontend Team đọc tài liệu Socket.io / Redis pub/sub ở đây)
 */
class RealtimeDocs
{
    #[OA\Get(
        path: '/socket/ride.new_offer',
        summary: '[Realtime] Có cuốc xe mới (Gửi cho tài xế)',
        description: 'Lắng nghe sự kiện qua Redis (ride.communication.events) hoặc Socket.io (room user:{userId})',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Event Payload',
                content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RideNewOffer')
            )
        ]
    )]
    public function docRideNewOffer(): void {}

    #[OA\Get(
        path: '/socket/ride.accepted',
        summary: '[Realtime] Tài xế đã nhận cuốc (Gửi cho khách hàng)',
        description: 'Lắng nghe sự kiện qua Socket.io room ride:{rideId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Event Payload',
                content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RideAccepted')
            )
        ]
    )]
    public function docRideAccepted(): void {}

    #[OA\Get(
        path: '/socket/ride.arrived',
        summary: '[Realtime] Tài xế đã đến điểm đón (Gửi cho khách hàng)',
        description: 'Lắng nghe sự kiện qua Socket.io room ride:{rideId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Event Payload',
                content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RideArrived')
            )
        ]
    )]
    public function docRideArrived(): void {}

    #[OA\Get(
        path: '/socket/ride.picked_up',
        summary: '[Realtime] Tài xế đã đón khách (Gửi cho khách hàng)',
        description: 'Lắng nghe sự kiện qua Socket.io room ride:{rideId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Event Payload',
                content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RidePickedUp')
            )
        ]
    )]
    public function docRidePickedUp(): void {}

    #[OA\Get(
        path: '/socket/ride.started',
        summary: '[Realtime] Chuyến xe bắt đầu di chuyển (Gửi cho khách hàng)',
        description: 'Lắng nghe sự kiện qua Socket.io room ride:{rideId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Event Payload',
                content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RideStarted')
            )
        ]
    )]
    public function docRideStarted(): void {}

    #[OA\Get(
        path: '/socket/ride.completed',
        summary: '[Realtime] Chuyến xe hoàn thành (Gửi cho khách hàng)',
        description: 'Lắng nghe sự kiện qua Socket.io room ride:{rideId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Event Payload',
                content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RideCompleted')
            )
        ]
    )]
    public function docRideCompleted(): void {}

    #[OA\Get(
        path: '/socket/ride.cancelled',
        summary: '[Realtime] Chuyến xe bị hủy (Gửi cho tài xế/khách hàng)',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Event Payload',
                content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RideCancelled')
            )
        ]
    )]
    public function docRideCancelled(): void {}

    #[OA\Get(
        path: '/socket/tracking.location.updated',
        summary: '[Realtime] Cập nhật vị trí GPS',
        description: 'Tài xế gửi lên Socket.io thông qua event `driver:location`. Khách hàng nghe ở room `ride:{rideId}` event `tracking.location.updated`.',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Event Payload',
                content: new OA\JsonContent(ref: '#/components/schemas/Realtime_LocationUpdated')
            )
        ]
    )]
    public function docLocationUpdated(): void {}

    #[OA\Get(
        path: '/socket/notification.created',
        summary: '[Realtime] Có thông báo hệ thống mới',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Event Payload',
                content: new OA\JsonContent(ref: '#/components/schemas/Realtime_NotificationCreated')
            )
        ]
    )]
    public function docNotificationCreated(): void {}

    #[OA\Get(
        path: '/socket/driver.application_rejected',
        summary: '[Realtime] Hồ sơ tài xế bị từ chối',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_DriverApplicationRejected'))
        ]
    )]
    public function docDriverApplicationRejected(): void {}

    #[OA\Get(
        path: '/socket/driver.application_approved',
        summary: '[Realtime] Hồ sơ tài xế được duyệt',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_DriverApplicationApproved'))
        ]
    )]
    public function docDriverApplicationApproved(): void {}

    #[OA\Get(
        path: '/socket/user.status_updated',
        summary: '[Realtime] Trạng thái người dùng thay đổi',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_UserStatusUpdated'))
        ]
    )]
    public function docUserStatusUpdated(): void {}

    #[OA\Get(
        path: '/socket/user.warned',
        summary: '[Realtime] Người dùng bị cảnh cáo',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_UserWarned'))
        ]
    )]
    public function docUserWarned(): void {}

    #[OA\Get(
        path: '/socket/ride.scheduled_pushed_to_pool',
        summary: '[Realtime] Chuyến xe đặt trước được đẩy vào pool',
        description: 'Lắng nghe sự kiện qua Socket.io',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RideScheduledPushedToPool'))
        ]
    )]
    public function docRideScheduledPushedToPool(): void {}

    #[OA\Get(
        path: '/socket/ride.cancellation_responded',
        summary: '[Realtime] Yêu cầu hủy chuyến được phản hồi',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RideCancellationResponded'))
        ]
    )]
    public function docRideCancellationResponded(): void {}

    #[OA\Get(
        path: '/socket/ride.cancellation_requested',
        summary: '[Realtime] Có yêu cầu hủy chuyến',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RideCancellationRequested'))
        ]
    )]
    public function docRideCancellationRequested(): void {}

    #[OA\Get(
        path: '/socket/ride.assigned_by_admin',
        summary: '[Realtime] Chuyến xe được gán bởi admin',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RideAssignedByAdmin'))
        ]
    )]
    public function docRideAssignedByAdmin(): void {}

    #[OA\Get(
        path: '/socket/ride.pickup_proof_captured',
        summary: '[Realtime] Tài xế chụp ảnh nhận hàng',
        description: 'Lắng nghe sự kiện qua Socket.io room ride:{rideId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RidePickupProofCaptured'))
        ]
    )]
    public function docRidePickupProofCaptured(): void {}

    #[OA\Get(
        path: '/socket/ride.delivery_proof_captured',
        summary: '[Realtime] Tài xế chụp ảnh giao hàng',
        description: 'Lắng nghe sự kiện qua Socket.io room ride:{rideId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_RideDeliveryProofCaptured'))
        ]
    )]
    public function docRideDeliveryProofCaptured(): void {}

    #[OA\Get(
        path: '/socket/food_order.updated',
        summary: '[Realtime] Trạng thái đơn hàng thay đổi',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_FoodOrderUpdated'))
        ]
    )]
    public function docFoodOrderUpdated(): void {}

    #[OA\Get(
        path: '/socket/food_order.cancellation_handled',
        summary: '[Realtime] Yêu cầu hủy đơn hàng được xử lý',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_FoodCancellationHandled'))
        ]
    )]
    public function docFoodCancellationHandled(): void {}

    #[OA\Get(
        path: '/socket/merchant.approved',
        summary: '[Realtime] Hồ sơ nhà hàng được duyệt',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_MerchantApproved'))
        ]
    )]
    public function docMerchantApproved(): void {}

    #[OA\Get(
        path: '/socket/notification.unread_count_updated',
        summary: '[Realtime] Số lượng thông báo chưa đọc thay đổi',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_NotificationUnreadCountUpdated'))
        ]
    )]
    public function docNotificationUnreadCountUpdated(): void {}

    #[OA\Get(
        path: '/socket/food.order_created',
        summary: '[Realtime] Có đơn hàng mới',
        description: 'Lắng nghe sự kiện qua Socket.io room merchant:{merchantId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_FoodOrderCreated'))
        ]
    )]
    public function docFoodOrderCreated(): void {}

    #[OA\Get(
        path: '/socket/finance.refund.processed',
        summary: '[Realtime] Hoàn tiền được xử lý',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_FinanceRefundProcessed'))
        ]
    )]
    public function docFinanceRefundProcessed(): void {}

    #[OA\Get(
        path: '/socket/complaint.handled',
        summary: '[Realtime] Khiếu nại được xử lý',
        description: 'Lắng nghe sự kiện qua Socket.io room user:{userId}',
        tags: ['Z - Realtime Events'],
        responses: [
            new OA\Response(response: 200, description: 'Event Payload', content: new OA\JsonContent(ref: '#/components/schemas/Realtime_ComplaintHandled'))
        ]
    )]
    public function docComplaintHandled(): void {}
}
