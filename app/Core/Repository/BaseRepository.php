<?php

namespace App\Core\Repository;

use App\Core\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct()
    {
        $this->setModel();
    }

    /**
     * Define the model class specific for this repository
     * @return string
     */
    abstract public function getModel();

    /**
     * Instantiate the model
     */
    public function setModel(): void
    {
        $model = app()->make($this->getModel());

        if (!$model instanceof Model) {
            throw new \Exception("Class {$this->getModel()} must be an instance of Eloquent Model");
        }

        $this->model = $model;
    }

    public function query(): Builder
    {
        return $this->model->query();
    }

    public function getModelInstance(): Model
    {
        return $this->model;
    }

    public function getAll(array $columns = ['*'], array $relations = []): \Illuminate\Support\Collection
    {
        return $this->model->with($relations)->get($columns);
    }


    public function find(int|string $id): ?Model
    {
        return $this->query()->find($id);
    }
    public function findById(int|string $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->select($columns)->find($id);
    }

    public function findByCondition(array $conditions, array $relations = []): ?Model
    {
        return $this->model->with($relations)->where($conditions)->first();
    }

    public function firstWhere(string $column, mixed $value, array $relations = []): ?Model
    {
        return $this->query()
            ->with($relations)
            ->where($column, $value)
            ->first();
    }

    /**
     * Trả về Query Builder nếu cần custom query phức tạp ở Child Repositories
     */
    protected function getQuery(): Builder
    {
        return $this->model->newQuery();
    }

    public function create(array $attributes): Model
    {
        return $this->model->create($attributes);
    }

    public function updateById($id, array $attributes): ?Model
    {
        $record = $this->find($id);

        if (!$record) {
            return null;
        }

        $record->update($attributes);

        return $record->fresh();
    }

    public function deleteById($id)
    {
        $record = $this->find($id);
        if ($record) {
            $record->delete();
            return true;
        }
        return false;
    }

    public function findOrFail($id): Model
    {
        return $this->query()->findOrFail($id);
    }
}
