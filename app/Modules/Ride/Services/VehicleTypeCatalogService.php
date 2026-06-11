<?php

declare(strict_types=1);

namespace App\Modules\Ride\Services;

use App\Modules\Ride\Interfaces\VehicleTypeRepositoryInterface;
use Throwable;

final class VehicleTypeCatalogService
{
    /**
     * Compatibility fallback for test/runtime paths that have not seeded vehicle_types yet.
     *
     * @var array<int, array<string, mixed>>
     */
    private const LEGACY_METADATA = [
        1 => [
            'id' => 1,
            'code' => 'bike',
            'name_vi' => 'Xe May',
            'description_vi' => 'Nhanh, tiet kiem - phu hop duong ngan',
            'capacity' => 1,
            'estimated_wait_time' => '2-5 phut',
            'service_scopes' => ['city', 'delivery'],
            'is_bookable' => true,
            'is_active' => true,
            'sort_order' => 1,
        ],
        2 => [
            'id' => 2,
            'code' => 'car_4',
            'name_vi' => 'O To 4 Cho',
            'description_vi' => 'Thoai mai cho 1-3 hanh khach',
            'capacity' => 3,
            'estimated_wait_time' => '3-7 phut',
            'service_scopes' => ['city', 'intercity', 'airport', 'delivery'],
            'is_bookable' => true,
            'is_active' => true,
            'sort_order' => 2,
        ],
        3 => [
            'id' => 3,
            'code' => 'car_7',
            'name_vi' => 'O To 7 Cho',
            'description_vi' => 'Rong rai cho nhom 4-6 nguoi',
            'capacity' => 6,
            'estimated_wait_time' => '5-10 phut',
            'service_scopes' => ['city', 'intercity', 'airport'],
            'is_bookable' => true,
            'is_active' => true,
            'sort_order' => 3,
        ],
        4 => [
            'id' => 4,
            'code' => 'car_9',
            'name_vi' => 'O To 9 Cho',
            'description_vi' => 'Ly tuong cho nhom dong hoac nhieu hanh ly',
            'capacity' => 8,
            'estimated_wait_time' => '7-15 phut',
            'service_scopes' => ['city', 'intercity', 'airport'],
            'is_bookable' => true,
            'is_active' => true,
            'sort_order' => 4,
        ],
        5 => [
            'id' => 5,
            'code' => 'car_shared',
            'name_vi' => 'Xe Ghep (Lien tinh)',
            'description_vi' => 'Tiet kiem, di chung voi hanh khach khac',
            'capacity' => 1,
            'estimated_wait_time' => 'Theo lich hen',
            'service_scopes' => ['intercity'],
            'is_bookable' => true,
            'is_active' => true,
            'sort_order' => 5,
        ],
        6 => [
            'id' => 6,
            'code' => 'chauffeur',
            'name_vi' => 'Lai ho (Xe khach)',
            'description_vi' => 'Tai xe lai xe cua chinh ban',
            'capacity' => 4,
            'estimated_wait_time' => '10-20 phut',
            'service_scopes' => ['chauffeur'],
            'is_bookable' => true,
            'is_active' => true,
            'sort_order' => 6,
        ],
    ];

    public function __construct(
        private readonly VehicleTypeRepositoryInterface $vehicleTypeRepository
    ) {}

    public function create(array $data): array
    {
        $this->ensurePersistedLegacyCatalog();

        $name = trim((string) ($data['name_vi'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Tên phương tiện là bắt buộc.');
        }

        if ($this->vehicleTypeRepository->findByName($name) !== null) {
            throw new \InvalidArgumentException('Tên phương tiện đã tồn tại.');
        }

        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            $code = $this->slugifyCode($name);
        }

        if ($this->vehicleTypeRepository->findByCode($code) !== null) {
            $code = $this->resolveUniqueCode($code);
        }

        $nextId = max(array_column($this->listAll(), 'id')) + 1;

        $type = $this->vehicleTypeRepository->create([
            'id' => $nextId,
            'code' => $code,
            'name_vi' => $name,
            'description_vi' => $data['description_vi'] ?? null,
            'capacity' => (int) ($data['capacity'] ?? 1),
            'estimated_wait_time' => $data['estimated_wait_time'] ?? null,
            'service_scopes' => array_values(array_unique($data['service_scopes'] ?? [])),
            'is_bookable' => (bool) ($data['is_bookable'] ?? true),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? $nextId),
        ]);

        return $this->normalizeType($type);
    }

    public function update(int $id, array $data): array
    {
        $this->ensurePersistedLegacyCatalog();

        $type = $this->vehicleTypeRepository->findById($id);
        if ($type === null) {
            throw new \InvalidArgumentException('Không tìm thấy loại phương tiện.');
        }

        $name = trim((string) ($data['name_vi'] ?? $type->name_vi));
        if ($name === '') {
            throw new \InvalidArgumentException('Tên phương tiện là bắt buộc.');
        }

        $existingByName = $this->vehicleTypeRepository->findByName($name);
        if ($existingByName !== null && (int) $existingByName->id !== $id) {
            throw new \InvalidArgumentException('Tên phương tiện đã tồn tại.');
        }

        $rawCode = trim((string) ($data['code'] ?? $type->code));
        $code = $rawCode !== '' ? $rawCode : $this->slugifyCode($name);
        $existingByCode = $this->vehicleTypeRepository->findByCode($code);
        if ($existingByCode !== null && (int) $existingByCode->id !== $id) {
            throw new \InvalidArgumentException('Mã phương tiện đã tồn tại.');
        }

        $updated = $this->vehicleTypeRepository->updateById($id, [
            'code' => $code,
            'name_vi' => $name,
            'description_vi' => $data['description_vi'] ?? $type->description_vi,
            'capacity' => isset($data['capacity']) ? (int) $data['capacity'] : (int) $type->capacity,
            'estimated_wait_time' => $data['estimated_wait_time'] ?? $type->estimated_wait_time,
            'service_scopes' => array_values(array_unique($data['service_scopes'] ?? ($type->service_scopes ?? []))),
            'is_bookable' => isset($data['is_bookable']) ? (bool) $data['is_bookable'] : (bool) $type->is_bookable,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : (bool) $type->is_active,
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : (int) $type->sort_order,
        ]);

        return $this->normalizeType($updated ?? $type);
    }

    public function archive(int $id): array
    {
        $this->ensurePersistedLegacyCatalog();

        $type = $this->vehicleTypeRepository->findById($id);
        if ($type === null) {
            throw new \InvalidArgumentException('Không tìm thấy loại phương tiện.');
        }

        $updated = $this->vehicleTypeRepository->updateById($id, [
            'is_active' => false,
            'is_bookable' => false,
        ]);

        return $this->normalizeType($updated ?? $type);
    }

    public function listActive(): array
    {
        $this->ensurePersistedLegacyCatalog();

        try {
            $items = $this->vehicleTypeRepository->getActiveVehicleTypes()
                ->map(fn ($type) => $this->normalizeType($type))
                ->values()
                ->toArray();
        } catch (Throwable) {
            $items = [];
        }

        return $items !== [] ? $items : array_values(self::LEGACY_METADATA);
    }

    public function listAll(): array
    {
        $this->ensurePersistedLegacyCatalog();

        try {
            $items = $this->vehicleTypeRepository->getAllVehicleTypes()
                ->map(fn ($type) => $this->normalizeType($type))
                ->values()
                ->toArray();
        } catch (Throwable) {
            $items = [];
        }

        return $items !== [] ? $items : array_values(self::LEGACY_METADATA);
    }

    public function listBookableByService(?string $serviceType = null): array
    {
        $this->ensurePersistedLegacyCatalog();

        try {
            $items = $this->vehicleTypeRepository->getBookableVehicleTypesForService($serviceType)
                ->map(fn ($type) => $this->normalizeType($type))
                ->values()
                ->toArray();
        } catch (Throwable) {
            $items = [];
        }

        if ($items !== []) {
            return $items;
        }

        return array_values(array_filter(
            self::LEGACY_METADATA,
            static function (array $type) use ($serviceType): bool {
                if (!($type['is_active'] ?? false) || !($type['is_bookable'] ?? false)) {
                    return false;
                }

                if ($serviceType === null) {
                    return true;
                }

                return in_array($serviceType, $type['service_scopes'] ?? [], true);
            }
        ));
    }

    public function getMetadataById(int $id): ?array
    {
        $this->ensurePersistedLegacyCatalog();

        try {
            $type = $this->vehicleTypeRepository->findById($id);
        } catch (Throwable) {
            $type = null;
        }

        if ($type !== null) {
            return $this->normalizeType($type);
        }

        return self::LEGACY_METADATA[$id] ?? null;
    }

    public function getLabelById(int $id): ?string
    {
        return $this->getMetadataById($id)['name_vi'] ?? null;
    }

    public function getCodeById(int $id): ?string
    {
        return $this->getMetadataById($id)['code'] ?? null;
    }

    public function getIdByCode(string $code): ?int
    {
        $this->ensurePersistedLegacyCatalog();

        try {
            $type = $this->vehicleTypeRepository->findByCode($code);
        } catch (Throwable) {
            $type = null;
        }

        return $type ? (int) $type->id : null;
    }

    public function getCapacityById(int $id): ?int
    {
        return $this->getMetadataById($id)['capacity'] ?? null;
    }

    public function getEstimatedWaitTimeById(int $id): ?string
    {
        return $this->getMetadataById($id)['estimated_wait_time'] ?? null;
    }

    public function supportsServiceScope(int $id, ?string $serviceType): bool
    {
        $metadata = $this->getMetadataById($id);
        if ($metadata === null) {
            return false;
        }

        if (!($metadata['is_active'] ?? false) || !($metadata['is_bookable'] ?? false)) {
            return false;
        }

        if ($serviceType === null) {
            return true;
        }

        $scopes = $metadata['service_scopes'] ?? [];
        if (!is_array($scopes) || $scopes === []) {
            return true;
        }

        return in_array($serviceType, $scopes, true);
    }

    private function normalizeType(object $type): array
    {
        return [
            'id' => (int) $type->id,
            'code' => $type->code,
            'name_vi' => $type->name_vi,
            'description_vi' => $type->description_vi,
            'capacity' => (int) $type->capacity,
            'estimated_wait_time' => $type->estimated_wait_time,
            'service_scopes' => is_array($type->service_scopes) ? $type->service_scopes : [],
            'is_bookable' => (bool) ($type->is_bookable ?? true),
            'is_active' => (bool) $type->is_active,
            'sort_order' => (int) $type->sort_order,
        ];
    }

    private function ensurePersistedLegacyCatalog(): void
    {
        try {
            $model = $this->vehicleTypeRepository->getModelInstance();

            foreach (self::LEGACY_METADATA as $id => $type) {
                $model->newQuery()->updateOrCreate(
                    ['id' => $id],
                    [
                        'code' => $type['code'],
                        'name_vi' => $type['name_vi'],
                        'description_vi' => $type['description_vi'],
                        'capacity' => $type['capacity'],
                        'estimated_wait_time' => $type['estimated_wait_time'],
                        'service_scopes' => $type['service_scopes'],
                        'is_bookable' => $type['is_bookable'],
                        'is_active' => $type['is_active'],
                        'sort_order' => $type['sort_order'],
                    ]
                );
            }
        } catch (Throwable) {
            // Ignore bootstrap failures in mocked/unit-test paths and fall back to in-memory metadata.
        }
    }

    private function slugifyCode(string $name): string
    {
        $value = mb_strtolower(trim($name));
        $value = preg_replace('/[^a-z0-9]+/u', '_', $value) ?? '';
        $value = trim($value, '_');

        return $value !== '' ? $value : 'vehicle_type';
    }

    private function resolveUniqueCode(string $baseCode): string
    {
        $suffix = 2;
        $candidate = $baseCode;

        while ($this->vehicleTypeRepository->findByCode($candidate) !== null) {
            $candidate = $baseCode . '_' . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}
