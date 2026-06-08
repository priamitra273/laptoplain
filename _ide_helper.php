<?php
/* @noinspection ALL */
// @formatter:off
// phpcs:ignoreFile

/**
 * A helper file for Laravel, to provide autocomplete information to your IDE
 * Generated for Laravel 12.36.1.
 *
 * This file should not be included in your code, only analyzed by your IDE!
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 * @see https://github.com/barryvdh/laravel-ide-helper
 */
namespace App\Facades {
    /**
     */
    class Sqids {
        /**
         * @static
         */
        public static function encode($value)
        {
            /** @var \App\Services\SqidsService $instance */
            return $instance->encode($value);
        }

        /**
         * @static
         */
        public static function decode($hashed)
        {
            /** @var \App\Services\SqidsService $instance */
            return $instance->decode($hashed);
        }

        /**
         * @static
         */
        public static function decodeOrFail($encodedId)
        {
            /** @var \App\Services\SqidsService $instance */
            return $instance->decodeOrFail($encodedId);
        }

        /**
         * @static
         */
        public static function rec_encode_ids_in_list($data)
        {
            /** @var \App\Services\SqidsService $instance */
            return $instance->rec_encode_ids_in_list($data);
        }

        /**
         * @static
         */
        public static function rec_decode_ids_in_list($data)
        {
            /** @var \App\Services\SqidsService $instance */
            return $instance->rec_decode_ids_in_list($data);
        }

            }
    /**
     */
    class TaskNotification {
        /**
         * @static
         */
        public static function createTaskNotification($task, $userIds, $type)
        {
            /** @var \App\Services\TaskNotificationService $instance */
            return $instance->createTaskNotification($task, $userIds, $type);
        }

            }
    }

namespace Maatwebsite\Excel\Facades {
    /**
     */
    class Excel {
        /**
         * @param object $export
         * @param string|null $fileName
         * @param string $writerType
         * @param array $headers
         * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
         * @throws \PhpOffice\PhpSpreadsheet\Exception
         * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
         * @static
         */
        public static function download($export, $fileName, $writerType = null, $headers = [])
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->download($export, $fileName, $writerType, $headers);
        }

        /**
         * @param string|null $disk Fallback for usage with named properties
         * @param object $export
         * @param string $filePath
         * @param string|null $diskName
         * @param string $writerType
         * @param mixed $diskOptions
         * @return bool
         * @throws \PhpOffice\PhpSpreadsheet\Exception
         * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
         * @static
         */
        public static function store($export, $filePath, $diskName = null, $writerType = null, $diskOptions = [], $disk = null)
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->store($export, $filePath, $diskName, $writerType, $diskOptions, $disk);
        }

        /**
         * @param object $export
         * @param string $filePath
         * @param string|null $disk
         * @param string $writerType
         * @param mixed $diskOptions
         * @return \Illuminate\Foundation\Bus\PendingDispatch
         * @static
         */
        public static function queue($export, $filePath, $disk = null, $writerType = null, $diskOptions = [])
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->queue($export, $filePath, $disk, $writerType, $diskOptions);
        }

        /**
         * @param object $export
         * @param string $writerType
         * @return string
         * @static
         */
        public static function raw($export, $writerType)
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->raw($export, $writerType);
        }

        /**
         * @param object $import
         * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $filePath
         * @param string|null $disk
         * @param string|null $readerType
         * @return \Maatwebsite\Excel\Reader|\Illuminate\Foundation\Bus\PendingDispatch
         * @static
         */
        public static function import($import, $filePath, $disk = null, $readerType = null)
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->import($import, $filePath, $disk, $readerType);
        }

        /**
         * @param object $import
         * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $filePath
         * @param string|null $disk
         * @param string|null $readerType
         * @return array
         * @static
         */
        public static function toArray($import, $filePath, $disk = null, $readerType = null)
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->toArray($import, $filePath, $disk, $readerType);
        }

        /**
         * @param object $import
         * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $filePath
         * @param string|null $disk
         * @param string|null $readerType
         * @return \Illuminate\Support\Collection
         * @static
         */
        public static function toCollection($import, $filePath, $disk = null, $readerType = null)
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->toCollection($import, $filePath, $disk, $readerType);
        }

        /**
         * @param \Illuminate\Contracts\Queue\ShouldQueue $import
         * @param string|\Symfony\Component\HttpFoundation\File\UploadedFile $filePath
         * @param string|null $disk
         * @param string $readerType
         * @return \Illuminate\Foundation\Bus\PendingDispatch
         * @static
         */
        public static function queueImport($import, $filePath, $disk = null, $readerType = null)
        {
            /** @var \Maatwebsite\Excel\Excel $instance */
            return $instance->queueImport($import, $filePath, $disk, $readerType);
        }

        /**
         * Register a custom macro.
         *
         * @param string $name
         * @param object|callable $macro
         * @param-closure-this static  $macro
         * @return void
         * @static
         */
        public static function macro($name, $macro)
        {
            \Maatwebsite\Excel\Excel::macro($name, $macro);
        }

        /**
         * Mix another object into the class.
         *
         * @param object $mixin
         * @param bool $replace
         * @return void
         * @throws \ReflectionException
         * @static
         */
        public static function mixin($mixin, $replace = true)
        {
            \Maatwebsite\Excel\Excel::mixin($mixin, $replace);
        }

        /**
         * Checks if macro is registered.
         *
         * @param string $name
         * @return bool
         * @static
         */
        public static function hasMacro($name)
        {
            return \Maatwebsite\Excel\Excel::hasMacro($name);
        }

        /**
         * Flush the existing macros.
         *
         * @return void
         * @static
         */
        public static function flushMacros()
        {
            \Maatwebsite\Excel\Excel::flushMacros();
        }

        /**
         * @param string $concern
         * @param callable $handler
         * @param string $event
         * @static
         */
        public static function extend($concern, $handler, $event = 'Maatwebsite\\Excel\\Events\\BeforeWriting')
        {
            return \Maatwebsite\Excel\Excel::extend($concern, $handler, $event);
        }

        /**
         * When asserting downloaded, stored, queued or imported, use regular expression
         * to look for a matching file path.
         *
         * @return void
         * @static
         */
        public static function matchByRegex()
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            $instance->matchByRegex();
        }

        /**
         * When asserting downloaded, stored, queued or imported, use regular string
         * comparison for matching file path.
         *
         * @return void
         * @static
         */
        public static function doNotMatchByRegex()
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            $instance->doNotMatchByRegex();
        }

        /**
         * @param string $fileName
         * @param callable|null $callback
         * @static
         */
        public static function assertDownloaded($fileName, $callback = null)
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            return $instance->assertDownloaded($fileName, $callback);
        }

        /**
         * @param string $filePath
         * @param string|callable|null $disk
         * @param callable|null $callback
         * @static
         */
        public static function assertStored($filePath, $disk = null, $callback = null)
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            return $instance->assertStored($filePath, $disk, $callback);
        }

        /**
         * @param string $filePath
         * @param string|callable|null $disk
         * @param callable|null $callback
         * @static
         */
        public static function assertQueued($filePath, $disk = null, $callback = null)
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            return $instance->assertQueued($filePath, $disk, $callback);
        }

        /**
         * @static
         */
        public static function assertQueuedWithChain($chain)
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            return $instance->assertQueuedWithChain($chain);
        }

        /**
         * @param string $classname
         * @param callable|null $callback
         * @static
         */
        public static function assertExportedInRaw($classname, $callback = null)
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            return $instance->assertExportedInRaw($classname, $callback);
        }

        /**
         * @param string $filePath
         * @param string|callable|null $disk
         * @param callable|null $callback
         * @static
         */
        public static function assertImported($filePath, $disk = null, $callback = null)
        {
            /** @var \Maatwebsite\Excel\Fakes\ExcelFake $instance */
            return $instance->assertImported($filePath, $disk, $callback);
        }

            }
    }

namespace Illuminate\Support {
    /**
     * @template TKey of array-key
     * @template-covariant TValue
     * @implements \ArrayAccess<TKey, TValue>
     * @implements \Illuminate\Support\Enumerable<TKey, TValue>
     */
    class Collection {
        /**
         * @see \Maatwebsite\Excel\Mixins\DownloadCollectionMixin::downloadExcel()
         * @param string $fileName
         * @param string|null $writerType
         * @param mixed $withHeadings
         * @param array $responseHeaders
         * @static
         */
        public static function downloadExcel($fileName, $writerType = null, $withHeadings = false, $responseHeaders = [])
        {
            return \Illuminate\Support\Collection::downloadExcel($fileName, $writerType, $withHeadings, $responseHeaders);
        }

        /**
         * @see \Maatwebsite\Excel\Mixins\StoreCollectionMixin::storeExcel()
         * @param string $filePath
         * @param string|null $disk
         * @param string|null $writerType
         * @param mixed $withHeadings
         * @static
         */
        public static function storeExcel($filePath, $disk = null, $writerType = null, $withHeadings = false)
        {
            return \Illuminate\Support\Collection::storeExcel($filePath, $disk, $writerType, $withHeadings);
        }

            }
    }

namespace Illuminate\Http {
    /**
     */
    class Request extends \Symfony\Component\HttpFoundation\Request {
        /**
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestValidation()
         * @param array $rules
         * @param mixed $params
         * @static
         */
        public static function validate($rules, ...$params)
        {
            return \Illuminate\Http\Request::validate($rules, ...$params);
        }

        /**
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestValidation()
         * @param string $errorBag
         * @param array $rules
         * @param mixed $params
         * @static
         */
        public static function validateWithBag($errorBag, $rules, ...$params)
        {
            return \Illuminate\Http\Request::validateWithBag($errorBag, $rules, ...$params);
        }

        /**
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestSignatureValidation()
         * @param mixed $absolute
         * @static
         */
        public static function hasValidSignature($absolute = true)
        {
            return \Illuminate\Http\Request::hasValidSignature($absolute);
        }

        /**
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestSignatureValidation()
         * @static
         */
        public static function hasValidRelativeSignature()
        {
            return \Illuminate\Http\Request::hasValidRelativeSignature();
        }

        /**
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestSignatureValidation()
         * @param mixed $ignoreQuery
         * @param mixed $absolute
         * @static
         */
        public static function hasValidSignatureWhileIgnoring($ignoreQuery = [], $absolute = true)
        {
            return \Illuminate\Http\Request::hasValidSignatureWhileIgnoring($ignoreQuery, $absolute);
        }

        /**
         * @see \Illuminate\Foundation\Providers\FoundationServiceProvider::registerRequestSignatureValidation()
         * @param mixed $ignoreQuery
         * @static
         */
        public static function hasValidRelativeSignatureWhileIgnoring($ignoreQuery = [])
        {
            return \Illuminate\Http\Request::hasValidRelativeSignatureWhileIgnoring($ignoreQuery);
        }

        /**
         * @see \Inertia\ServiceProvider::registerRequestMacro()
         * @static
         */
        public static function inertia()
        {
            return \Illuminate\Http\Request::inertia();
        }

            }
    }

namespace Illuminate\Routing {
    /**
     * @mixin \Illuminate\Routing\RouteRegistrar
     */
    class Router {
        /**
         * @param array<array-key, mixed> $props
         * @see \Inertia\ServiceProvider::registerRouterMacro()
         * @static
         */
        public static function inertia($uri, $component, $props = [])
        {
            return \Illuminate\Routing\Router::inertia($uri, $component, $props);
        }

            }
    /**
     */
    class Route {
        /**
         * @see \Spatie\Permission\PermissionServiceProvider::registerMacroHelpers()
         * @param mixed $roles
         * @static
         */
        public static function role($roles = [])
        {
            return \Illuminate\Routing\Route::role($roles);
        }

        /**
         * @see \Spatie\Permission\PermissionServiceProvider::registerMacroHelpers()
         * @param mixed $permissions
         * @static
         */
        public static function permission($permissions = [])
        {
            return \Illuminate\Routing\Route::permission($permissions);
        }

            }
    }

namespace Illuminate\Testing {
    /**
     * @template TResponse of \Symfony\Component\HttpFoundation\Response
     * @mixin \Illuminate\Http\Response
     */
    class TestResponse {
        /**
         * @see \Inertia\Testing\TestResponseMacros::assertInertia()
         * @param \Closure|null $callback
         * @static
         */
        public static function assertInertia($callback = null)
        {
            return \Illuminate\Testing\TestResponse::assertInertia($callback);
        }

        /**
         * @see \Inertia\Testing\TestResponseMacros::inertiaPage()
         * @static
         */
        public static function inertiaPage()
        {
            return \Illuminate\Testing\TestResponse::inertiaPage();
        }

        /**
         * @see \Inertia\Testing\TestResponseMacros::inertiaProps()
         * @param string|null $propName
         * @static
         */
        public static function inertiaProps($propName = null)
        {
            return \Illuminate\Testing\TestResponse::inertiaProps($propName);
        }

            }
    }

namespace Illuminate\Database\Query {
    /**
     */
    class Builder {
        /**
         * @see \Kirschbaum\PowerJoins\Mixins\QueryBuilderExtraMethods::getGroupBy()
         * @static
         */
        public static function getGroupBy()
        {
            return \Illuminate\Database\Query\Builder::getGroupBy();
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\QueryBuilderExtraMethods::getSelect()
         * @static
         */
        public static function getSelect()
        {
            return \Illuminate\Database\Query\Builder::getSelect();
        }

            }
    }

namespace Illuminate\Database\Eloquent\Relations {
    /**
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
     * @template TResult
     * @mixin \Illuminate\Database\Eloquent\Builder<TRelatedModel>
     */
    class Relation {
        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::performJoinForEloquentPowerJoins()
         * @param mixed $builder
         * @param mixed $joinType
         * @param mixed $callback
         * @param mixed $alias
         * @param bool $disableExtraConditions
         * @param string|null $morphable
         * @param bool $hasCheck
         * @static
         */
        public static function performJoinForEloquentPowerJoins($builder, $joinType = 'leftJoin', $callback = null, $alias = null, $disableExtraConditions = false, $morphable = null, $hasCheck = false)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::performJoinForEloquentPowerJoins($builder, $joinType, $callback, $alias, $disableExtraConditions, $morphable, $hasCheck);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::performJoinForEloquentPowerJoinsForBelongsTo()
         * @param mixed $query
         * @param mixed $joinType
         * @param mixed $callback
         * @param mixed $alias
         * @param bool $disableExtraConditions
         * @static
         */
        public static function performJoinForEloquentPowerJoinsForBelongsTo($query, $joinType, $callback = null, $alias = null, $disableExtraConditions = false)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::performJoinForEloquentPowerJoinsForBelongsTo($query, $joinType, $callback, $alias, $disableExtraConditions);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::performJoinForEloquentPowerJoinsForBelongsToMany()
         * @param mixed $builder
         * @param mixed $joinType
         * @param mixed $callback
         * @param mixed $alias
         * @param bool $disableExtraConditions
         * @static
         */
        public static function performJoinForEloquentPowerJoinsForBelongsToMany($builder, $joinType, $callback = null, $alias = null, $disableExtraConditions = false)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::performJoinForEloquentPowerJoinsForBelongsToMany($builder, $joinType, $callback, $alias, $disableExtraConditions);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::performJoinForEloquentPowerJoinsForMorphToMany()
         * @param mixed $builder
         * @param mixed $joinType
         * @param mixed $callback
         * @param mixed $alias
         * @param bool $disableExtraConditions
         * @static
         */
        public static function performJoinForEloquentPowerJoinsForMorphToMany($builder, $joinType, $callback = null, $alias = null, $disableExtraConditions = false)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::performJoinForEloquentPowerJoinsForMorphToMany($builder, $joinType, $callback, $alias, $disableExtraConditions);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::performJoinForEloquentPowerJoinsForMorph()
         * @param mixed $builder
         * @param mixed $joinType
         * @param mixed $callback
         * @param mixed $alias
         * @param bool $disableExtraConditions
         * @static
         */
        public static function performJoinForEloquentPowerJoinsForMorph($builder, $joinType, $callback = null, $alias = null, $disableExtraConditions = false)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::performJoinForEloquentPowerJoinsForMorph($builder, $joinType, $callback, $alias, $disableExtraConditions);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::performJoinForEloquentPowerJoinsForMorphTo()
         * @param mixed $builder
         * @param mixed $joinType
         * @param mixed $callback
         * @param mixed $alias
         * @param bool $disableExtraConditions
         * @param string|null $morphable
         * @static
         */
        public static function performJoinForEloquentPowerJoinsForMorphTo($builder, $joinType, $callback = null, $alias = null, $disableExtraConditions = false, $morphable = null)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::performJoinForEloquentPowerJoinsForMorphTo($builder, $joinType, $callback, $alias, $disableExtraConditions, $morphable);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::performJoinForEloquentPowerJoinsForHasMany()
         * @param mixed $builder
         * @param mixed $joinType
         * @param mixed $callback
         * @param mixed $alias
         * @param bool $disableExtraConditions
         * @param bool $hasCheck
         * @static
         */
        public static function performJoinForEloquentPowerJoinsForHasMany($builder, $joinType, $callback = null, $alias = null, $disableExtraConditions = false, $hasCheck = false)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::performJoinForEloquentPowerJoinsForHasMany($builder, $joinType, $callback, $alias, $disableExtraConditions, $hasCheck);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::performJoinForEloquentPowerJoinsForHasManyThrough()
         * @param mixed $builder
         * @param mixed $joinType
         * @param mixed $callback
         * @param mixed $alias
         * @param bool $disableExtraConditions
         * @static
         */
        public static function performJoinForEloquentPowerJoinsForHasManyThrough($builder, $joinType, $callback = null, $alias = null, $disableExtraConditions = false)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::performJoinForEloquentPowerJoinsForHasManyThrough($builder, $joinType, $callback, $alias, $disableExtraConditions);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::performHavingForEloquentPowerJoins()
         * @param mixed $builder
         * @param mixed $operator
         * @param mixed $count
         * @param string|null $morphable
         * @static
         */
        public static function performHavingForEloquentPowerJoins($builder, $operator, $count, $morphable = null)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::performHavingForEloquentPowerJoins($builder, $operator, $count, $morphable);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::usesSoftDeletes()
         * @param mixed $model
         * @static
         */
        public static function usesSoftDeletes($model)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::usesSoftDeletes($model);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::getThroughParent()
         * @static
         */
        public static function getThroughParent()
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::getThroughParent();
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::getFarParent()
         * @static
         */
        public static function getFarParent()
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::getFarParent();
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::applyExtraConditions()
         * @param \Kirschbaum\PowerJoins\PowerJoinClause $join
         * @static
         */
        public static function applyExtraConditions($join)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::applyExtraConditions($join);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::applyBasicCondition()
         * @param mixed $join
         * @param mixed $condition
         * @static
         */
        public static function applyBasicCondition($join, $condition)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::applyBasicCondition($join, $condition);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::applyNullCondition()
         * @param mixed $join
         * @param mixed $condition
         * @static
         */
        public static function applyNullCondition($join, $condition)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::applyNullCondition($join, $condition);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::applyNotNullCondition()
         * @param mixed $join
         * @param mixed $condition
         * @static
         */
        public static function applyNotNullCondition($join, $condition)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::applyNotNullCondition($join, $condition);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::applyNestedCondition()
         * @param mixed $join
         * @param mixed $condition
         * @static
         */
        public static function applyNestedCondition($join, $condition)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::applyNestedCondition($join, $condition);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::shouldNotApplyExtraCondition()
         * @param mixed $condition
         * @static
         */
        public static function shouldNotApplyExtraCondition($condition)
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::shouldNotApplyExtraCondition($condition);
        }

        /**
         * @see \Kirschbaum\PowerJoins\Mixins\RelationshipsExtraMethods::getPowerJoinExistenceCompareKey()
         * @static
         */
        public static function getPowerJoinExistenceCompareKey()
        {
            return \Illuminate\Database\Eloquent\Relations\Relation::getPowerJoinExistenceCompareKey();
        }

            }
    }


namespace  {
    class Sqids extends \App\Facades\Sqids {}
    class TaskNotification extends \App\Facades\TaskNotification {}
    class Excel extends \Maatwebsite\Excel\Facades\Excel {}
}





