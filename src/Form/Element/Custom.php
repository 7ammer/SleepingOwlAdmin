<?php

namespace SleepingOwl\Admin\Form\Element;

use Closure;
use Illuminate\Http\Request;
use SleepingOwl\Admin\Contracts\Template\MetaInterface;
use SleepingOwl\Admin\Contracts\Template\TemplateInterface;
use SleepingOwl\Admin\Form\FormElement;

class Custom extends FormElement
{
    /**
     * @var string|Closure
     */
    protected $display;

    /**
     * @var Closure
     */
    protected $callback;

    /**
     * Custom constructor.
     *
     * @param MetaInterface $meta
     * @param Closure|null $callback
     */
    public function __construct(MetaInterface $meta, Closure $callback = null)
    {
        if (! is_null($callback)) {
            $this->setCallback($callback);
        }

        parent::__construct($meta);
    }

    /**
     * @return Closure|string
     */
    public function getDisplay()
    {
        if (is_callable($this->display)) {
            return call_user_func($this->display, $this->getModel());
        }

        return $this->display;
    }

    /**
     * @param Closure|string $display
     *
     * @return $this
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * @return Closure
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param Closure $callback
     *
     * @return $this
     */
    public function setCallback(Closure $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return $this|Custom|mixed
     */
    public function render()
    {
        return $this->getDisplay();
    }

    /**
     * @param Request $request
     */
    public function save(Request $request)
    {
        $callback = $this->getCallback();

        if (is_callable($callback)) {
            call_user_func($callback, $this->getModel());
        }
    }
}
