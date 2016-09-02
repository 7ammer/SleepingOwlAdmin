<?php

namespace SleepingOwl\Admin\Form;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Support\Collection;
use KodiComponents\Support\HtmlAttributes;
use SleepingOwl\Admin\Contracts\AdminInterface;
use SleepingOwl\Admin\Contracts\DisplayInterface;
use SleepingOwl\Admin\Contracts\FormButtonsInterface;
use SleepingOwl\Admin\Contracts\FormElementInterface;
use SleepingOwl\Admin\Contracts\FormInterface;
use SleepingOwl\Admin\Contracts\ModelConfigurationInterface;
use SleepingOwl\Admin\Contracts\RepositoryInterface;
use SleepingOwl\Admin\Contracts\Template\MetaInterface;
use SleepingOwl\Admin\Form\Element\Upload;

class FormDefault extends FormElements implements DisplayInterface, FormInterface
{
    use HtmlAttributes;

    /**
     * View to render.
     * @var string|\Illuminate\View\View
     */
    protected $view = 'form.default';

    /**
     * @var FormButtons
     */
    protected $buttons;

    /**
     * Form action url.
     * @var string
     */
    protected $action;

    /**
     * Form related model instance.
     * @var Model
     */
    protected $model;

    /**
     * @var ModelConfigurationInterface
     */
    protected $modelConfiguration;

    /**
     * Currently loaded model id.
     * @var int
     */
    protected $id;

    /**
     * @var UrlGenerator
     */
    protected $urlGenerator;

    /**
     * FormDefault constructor.
     *
     * @param MetaInterface $meta
     * @param FormButtonsInterface $buttons
     * @param UrlGenerator $urlGenerator
     * @param array $elements
     */
    public function __construct(
        MetaInterface $meta,
        FormButtonsInterface $buttons,
        UrlGenerator $urlGenerator,
        array $elements = []
    )
    {
        $this->urlGenerator = $urlGenerator;

        parent::__construct($meta, $elements);

        $this->setButtons(
            $buttons
        );
    }

    /**
     * Initialize form.
     */
    public function initialize()
    {
        $this->setModel(
            $this->getModelConfiguration()->getModel()
        );

        parent::initialize();

        $this->getElements()->each(function ($element) {
            if ($element instanceof Upload and ! $this->hasHtmlAttribute('enctype')) {
                $this->setHtmlAttribute('enctype', 'multipart/form-data');
            }
        });

        $this->setHtmlAttribute('method', 'POST');

        $this->getButtons()->setModelConfiguration(
            $this->getModelConfiguration()
        );
    }

    /**
     * @return FormButtons
     */
    public function getButtons()
    {
        return $this->buttons;
    }

    /**
     * @param FormButtonsInterface $buttons
     *
     * @return $this
     */
    public function setButtons(FormButtonsInterface $buttons)
    {
        $this->buttons = $buttons;

        return $this;
    }

    /**
     * @return RepositoryInterface
     */
    public function getRepository()
    {
        return $this->getModelConfiguration()->getRepository();
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        if (is_null($this->action)) {
            $this->action = $action;
        }

        $this->setHtmlAttribute('action', $this->action);

        return $this;
    }

    /**
     * @param ModelConfigurationInterface $model
     *
     * @return $this
     */
    public function setModelConfiguration(ModelConfigurationInterface $model)
    {
        return parent::setModelConfiguration($model);
    }

    /**
     * @deprecated 4.5.0
     * @see getElements()
     *
     * @return Collection[]
     */
    public function getItems()
    {
        return $this->getElements();
    }

    /**
     * @deprecated 4.5.0
     * @see setElements()
     *
     * @param array|FormElementInterface $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        if (! is_array($items)) {
            $items = func_get_args();
        }

        return $this->setElements($items);
    }

    /**
     * @deprecated 4.5.0
     * @see addElement()
     *
     * @param FormElementInterface $item
     *
     * @return $this
     */
    public function addItem($item)
    {
        return $this->addElement($item);
    }

    /**
     * Set currently loaded model id.
     *
     * @param int $id
     */
    public function setId($id)
    {
        if (is_null($this->id) and ! is_null($id) and ($model = $this->getRepository()->find($id))) {
            $this->id = $id;
            $this->setModel($model);
        }
    }

    /**
     * @param Model $model
     *
     * @return $this
     */
    public function setModel(Model $model)
    {
        parent::setModel($model);

        $this->getButtons()->setModel($this->getModel());

        return $this;
    }

    /**
     * Save instance.
     *
     * @param ModelConfigurationInterface $modelConfiguration
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    public function saveForm(ModelConfigurationInterface $modelConfiguration, \Illuminate\Http\Request $request)
    {
        if ($modelConfiguration !== $this->getModelConfiguration()) {
            return;
        }

        parent::save($request);

        $this->saveBelongsToRelations();

        $loaded = $this->getModel()->exists;

        if ($modelConfiguration->fireEvent($loaded ? 'updating' : 'creating', true, $this->getModel()) === false) {
            return false;
        }

        if ($modelConfiguration->fireEvent('saving', true, $this->getModel()) === false) {
            return false;
        }


        $this->getModel()->save();

        $this->saveHasOneRelations();

        parent::afterSave($request);

        $modelConfiguration->fireEvent($loaded ? 'updated' : 'created', false, $this->getModel());
        $modelConfiguration->fireEvent('saved', false, $this->getModel());

        return true;
    }

    protected function saveBelongsToRelations()
    {
        $model = $this->getModel();

        foreach ($model->getRelations() as $name => $relation) {
            if ($model->{$name}() instanceof BelongsTo && ! is_null($relation)) {
                $relation->save();
                $model->{$name}()->associate($relation);
            }
        }
    }

    protected function saveHasOneRelations()
    {
        $model = $this->getModel();

        foreach ($model->getRelations() as $name => $relation) {
            if ($model->{$name}() instanceof HasOneOrMany && ! is_null($relation)) {
                if (is_array($relation) || $relation instanceof \Traversable) {
                    $model->{$name}()->saveMany($relation);
                } else {
                    $model->{$name}()->save($relation);
                }
            }
        }
    }

    /**
     * @param ModelConfigurationInterface $modelConfiguration
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Contracts\Validation\Factory $validator
     *
     * @return \Illuminate\Contracts\Validation\Validator|null
     */
    public function validateForm(ModelConfigurationInterface $modelConfiguration,
                                 \Illuminate\Http\Request $request,
                                 \Illuminate\Contracts\Validation\Factory $validator
    )
    {
        if ($modelConfiguration !== $this->getModelConfiguration()) {
            return;
        }

        $validator = $validator->make(
            $request->all(),
            $this->getValidationRules(),
            $this->getValidationMessages(),
            $this->getValidationLabels()
        );

        $verifier = app('validation.presence');
        $verifier->setConnection($this->getModel()->getConnectionName());
        $validator->setPresenceVerifier($verifier);

        if ($validator->fails()) {
            return $validator;
        }

        return true;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'items' => $this->getElements(),
            'instance' => $this->getModel(),
            'attributes' => $this->htmlAttributesToString(),
            'buttons' => $this->getButtons(),
            'backUrl' => session('_redirectBack', $this->urlGenerator->previous()),
        ];
    }
}
