<?php

namespace SleepingOwl\Admin\Display;

use Illuminate\Database\Eloquent\Model;
use KodiComponents\Support\HtmlAttributes;
use SleepingOwl\Admin\Contracts\AdminInterface;
use SleepingOwl\Admin\Contracts\ColumnInterface;
use SleepingOwl\Admin\Contracts\Display\TableHeaderColumnInterface;
use SleepingOwl\Admin\Contracts\ModelConfigurationInterface;
use SleepingOwl\Admin\Contracts\Template\TemplateInterface;
use SleepingOwl\Admin\Traits\Assets;

abstract class TableColumn implements ColumnInterface
{
    use HtmlAttributes, Assets;

    /**
     * Column header.
     *
     * @var TableHeaderColumnInterface
     */
    protected $header;

    /**
     * Model instance currently rendering.
     *
     * @var Model
     */
    protected $model;

    /**
     * Column appendant.
     *
     * @var ColumnInterface
     */
    protected $append;

    /**
     * Column width.
     *
     * @var string
     */
    protected $width = null;

    /**
     * @var string|\Illuminate\View\View
     */
    protected $view;

    /**
     * @var TemplateInterface
     */
    protected $template;

    /**
     * @var AdminInterface
     */
    protected $admin;

    /**
     * @var ModelConfigurationInterface
     */
    protected $modelConfiguration;

    /**
     * TableColumn constructor.
     *
     * @param AdminInterface $admin
     * @param TableHeaderColumnInterface $headerColumn
     * @param string|null $label
     */
    public function __construct(AdminInterface $admin, TableHeaderColumnInterface $headerColumn, $label = null)
    {
        $this->admin = $admin;
        $this->template = $admin->template();
        $this->header = $headerColumn;

        if (! is_null($label)) {
            $this->setLabel($label);
        }

        $this->initializePackage(
            $this->template->meta()
        );
    }

    /**
     * Initialize column.
     */
    public function initialize()
    {
        $this->includePackage(
            $this->template->meta()
        );
    }

    /**
     * @return TableHeaderColumnInterface
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * @return string|\Illuminate\View\View
     */
    public function getView()
    {
        if (is_null($this->view)) {
            $reflect = new \ReflectionClass($this);
            $this->view = 'column.'.strtolower($reflect->getShortName());
        }

        return $this->view;
    }

    /**
     * @param string|\Illuminate\View\View $view
     *
     * @return $this
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return ColumnInterface
     */
    public function getAppends()
    {
        return $this->append;
    }

    /**
     * @param ColumnInterface $append
     *
     * @return $this
     */
    public function append(ColumnInterface $append)
    {
        $this->append = $append;

        return $this;
    }

    /**
     * @return Model $model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param Model $model
     *
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
        $append = $this->getAppends();
        if (! is_null($append)) {
            $append->setModel($model);
        }

        return $this;
    }

    /**
     * @param ModelConfigurationInterface $model
     *
     * @return $this
     */
    public function setModelConfiguration(ModelConfigurationInterface $model)
    {
        $this->modelConfiguration = $model;

        $append = $this->getAppends();
        if (! is_null($append)) {
            $append->setModelConfiguration($model);
        }

        return $this;
    }

    /**
     * @return ModelConfigurationInterface
     */
    public function getModelConfiguration()
    {
        return $this->modelConfiguration;
    }

    /**
     * Set column header label.
     *
     * @param string $title
     *
     * @return $this
     */
    public function setLabel($title)
    {
        $this->getHeader()->setTitle($title);

        return $this;
    }

    /**
     * @param bool $orderable
     *
     * @return $this
     */
    public function setOrderable($orderable)
    {
        $this->getHeader()->setOrderable($orderable);

        return $this;
    }

    /**
     * Check if column is orderable.
     * @return bool
     */
    public function isOrderable()
    {
        return $this->getHeader()->isOrderable();
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'attributes' => $this->htmlAttributesToString(),
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->render();
    }

    /**
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function render()
    {
        return $this->template->view(
            $this->getView(),
            $this->toArray()
        );
    }
}
