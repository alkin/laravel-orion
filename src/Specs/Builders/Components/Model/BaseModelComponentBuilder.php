<?php

declare(strict_types=1);

namespace Orion\Specs\Builders\Components\Model;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orion\Http\Requests\Request;
use Orion\Http\Resources\Resource;
use Orion\Specs\Builders\Components\ModelComponentBuilder;
use Orion\ValueObjects\Specs\Component;

use function class_basename;

class BaseModelComponentBuilder extends ModelComponentBuilder
{
    /**
     * @param Model $resourceModel
     * @return Component
     * @throws Exception
     */
    public function build(Model $resourceModel, Resource $resourceResource, Request $resourceRequest): Component
    {
        $component = new Component();
        $component->title = class_basename($resourceModel);
        $component->type = 'object';

        $includedColumns = $this->resolveIncludedColumns($resourceModel, $resourceRequest);

        $component->properties = $this->getPropertiesFromSchema(
            $resourceModel,
            $includedColumns,
        );

        return $component;
    }

    /**
     * @param Model $resourceModel
     * @param array $includedColumns
     * @return array
     * @throws Exception
     */
    protected function getPropertiesFromSchema(Model $resourceModel, array $includedColumns = []): array
    {
        $columns = $this->schemaManager->getSchemaColumns($resourceModel);

        return collect($columns)
            ->filter(function (Column $column) use ($includedColumns) {
                return in_array($column->getName(), $includedColumns, true);
            })
            ->map(function (Column $column) use ($resourceModel) {
                $propertyClass = $this->schemaManager->resolveSchemaPropertyClass($column, $resourceModel);

                return $this->propertyBuilder->build($column, $propertyClass);
            })
            ->values()
            ->keyBy('name')
            ->toArray();
    }

    /**
     * @param Model $resourceModel
     * @return array
     */
    protected function resolveExcludedColumns(Model $resourceModel, Request $resourceRequest): array
    {
        $excludedColumns = [
            $resourceModel->getKeyName(),
            'created_at',
            'updated_at',
        ];

        if (method_exists($resourceModel, 'trashed')) {
            /** @var SoftDeletes $resourceModel */
            $excludedColumns[] = $resourceModel->getDeletedAtColumn();
        }

        return $excludedColumns;
    }

    /**
     * @param Model $resourceModel
     * @return array
     */
    protected function resolveIncludedColumns(Model $resourceModel, Request $resourceRequest): array
    {
        return collect($resourceRequest->commonRules())
            ->keys()
            ->map(fn ($i) => explode('.', (string) $i)[0])
            ->unique()
            ->all();
    }
}
