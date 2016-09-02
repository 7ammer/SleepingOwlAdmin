<?php

namespace SleepingOwl\Admin\Display;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Renderable;
use SleepingOwl\Admin\Contracts\ColumnInterface;
use SleepingOwl\Admin\Contracts\Template\MetaInterface;
use SleepingOwl\Admin\Display\Column\Control;
use SleepingOwl\Admin\Display\Extension\Columns;
use SleepingOwl\Admin\Display\Extension\ColumnFilters;
use SleepingOwl\Admin\Contracts\ColumnFilterInterface;
use SleepingOwl\Admin\Contracts\Display\DisplayExtensionInterface;

/**
 * Class DisplayTable.

 * @method Columns getColumns()
 * @method $this setColumns(ColumnInterface $column, ... $columns)
 *
 * @method ColumnFilters getColumnFilters()
 * @method $this setColumnFilters(ColumnFilterInterface $filters = null, ...$filters)
 */
class DisplayTable extends Display
{
    /**
     * @var string
     */
    protected $view = 'display.table';

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var int|null
     */
    protected $paginate = 25;

    /**
     * @var string
     */
    protected $pageName = 'page';

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var string|null
     */
    protected $newEntryButtonText;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Display constructor.
     *
     * @param MetaInterface $meta
     * @param Control $control
     * @param Request $request
     *
     * @internal param TemplateInterface $template
     */
    public function __construct(MetaInterface $meta, Control $control, Request $request)
    {
        parent::__construct($meta);

        $this->request = $request;

        $this->extend('columns', new Columns($control));
        $this->extend('column_filters', new ColumnFilters());
    }

    /**
     * Initialize display.
     */
    public function initialize()
    {
        parent::initialize();

        if ($this->getModelConfiguration()->isRestorableModel()) {
            $this->setApply(function ($q) {
                return $q->withTrashed();
            });
        }

        $this->getColumns()->all()->each(function(ColumnInterface $column) {
            $column->setModelConfiguration($this->getModelConfiguration());
        });

        foreach ($this->getColumnFilters()->all() as $columnFilter) {
            $columnFilter->setModelConfiguration($this->getModelConfiguration());
        }

        $this->setHtmlAttribute('class', 'table table-striped');
    }

    /**
     * @return null|string
     */
    public function getNewEntryButtonText()
    {
        if (is_null($this->newEntryButtonText)) {
            $this->newEntryButtonText = trans('sleeping_owl::lang.table.new-entry');
        }


        return $this->newEntryButtonText;
    }

    /**
     * @param string $newEntryButtonText
     *
     * @return $this
     */
    public function setNewEntryButtonText($newEntryButtonText)
    {
        $this->newEntryButtonText = $newEntryButtonText;

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * @param int    $perPage
     * @param string $pageName
     *
     * @return $this
     */
    public function paginate($perPage = 25, $pageName = 'page')
    {
        $this->paginate = (int) $perPage;
        $this->pageName = $pageName;

        return $this;
    }

    /**
     * @return $this
     */
    public function disablePagination()
    {
        $this->paginate = 0;

        return $this;
    }

    /**
     * @return bool
     */
    public function usePagination()
    {
        return $this->paginate > 0;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $model = $this->getModelConfiguration();

        $params = parent::toArray();

        $params['creatable'] = $model->isCreatable();
        $params['createUrl'] = $model->getCreateUrl($this->getParameters() + $this->request->all());
        $params['collection'] = $this->getCollection();

        $params['extensions'] = $this->getExtensions()
            ->filter(function (DisplayExtensionInterface $ext) {
                return $ext instanceof Renderable;
            })
            ->sortBy(function (DisplayExtensionInterface $extension) {
                return $extension->getOrder();
            });

        $params['newEntryButtonText'] = $this->getNewEntryButtonText();

        return $params;
    }

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        return $this->getTemplate()->view($this->getView(), $this->toArray());
    }

    /**
     * @return Collection
     * @throws \Exception
     */
    public function getCollection()
    {
        if (! is_null($this->collection)) {
            return $this->collection;
        }

        $query = $this->getRepository()->getQuery();

        $this->modifyQuery($query);

        return $this->collection = $this->usePagination()
            ? $query->paginate($this->paginate, ['*'], $this->pageName)
            : $query->get();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder|Builder $query
     */
    protected function modifyQuery(\Illuminate\Database\Eloquent\Builder $query)
    {
        $this->extensions->each(function (DisplayExtensionInterface $extension) use ($query) {
            $extension->modifyQuery($query);
        });
    }
}
