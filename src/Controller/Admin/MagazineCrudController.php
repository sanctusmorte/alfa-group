<?php

namespace App\Controller\Admin;

use App\Entity\Author;
use App\Entity\Magazine;
use App\Repository\AuthorRepository;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class MagazineCrudController extends AbstractCrudController implements CrudControllerInterface
{
    private $authorsForSelect;

    public function __construct(AuthorRepository $authorRepository)
    {
        $this->authorsForSelect = $this->getAuthorsForSelect($authorRepository->findAll());
    }

    private function getAuthorsForSelect($existAuthors)
    {
        $data = [];
        foreach ($existAuthors as $existAuthor) {
            $data[$existAuthor->getSurname() . ' ' . $existAuthor->getName()] = $existAuthor->getId();
        }

        return $data;
    }


    public static function getEntityFqcn(): string
    {
        return Magazine::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Редактирование журнала')
            ->setPageTitle('index', 'Список журналов')
            ->setPageTitle('new', 'Добавление нового журнала')
            ->overrideTemplate('crud/edit', '/admin/magazine-crud-controller/crud-edit.html.twig')
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-file-alt')->setLabel('Добавить журнал');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-file-alt')->setLabel('Удалить');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-file-alt')->setLabel('Редактировать');
            })
            ;
    }


    public function configureFields(string $pageName): iterable
    {
        $context = $this->get(AdminContextProvider::class)->getContext();
        $entity = $context->getEntity()->getInstance();
        $selectedAuthors = [];

        if ($entity !== null) {
            foreach ($entity->getAuthors() as $author) {
                $selectedAuthors[] = $author->getId();
            }
        }


        return [
            IdField::new('id', '#')->onlyOnIndex(),
            TextField::new('name', 'Название')
                ->setFormTypeOptions([
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                    ]
                ])
                ->setHelp('Обязательное поле'),
            TextField::new('description', 'Краткое описание'),
            DateTimeField::new('created', 'Дата создания')
                ->setFormTypeOption('disabled', 'disabled')
                ->onlyWhenUpdating()
                ->hideOnIndex()
                ->setTimezone('Europe/Moscow'),
            ImageField::new('imageUrl', 'Изображение')->onlyOnIndex(),
            TextField::new('image')->setFormType(FileType::class)
                ->onlyWhenUpdating()
                ->setFormTypeOptions([
                    'required' => true,
                    'mapped' => false,
                    'constraints' => [
                        new File([
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/png',
                            ],
                            'maxSize' => '2000k',
                            'mimeTypesMessage' => 'Please upload a valid JPEG or PNG images',
                        ]),

                    ]
                ])
                ->hideOnIndex(),
            AssociationField::new('authors', 'Количество авторов')->onlyOnIndex(),
            ChoiceField::new('authorsSelected', 'Выберите авторов журнала')
                ->hideOnIndex()
                ->setChoices($this->authorsForSelect)
                ->allowMultipleChoices(true)
                ->setFormTypeOptions([
                    'data' => $selectedAuthors,
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank()
                    ]
                ]),


        ];
    }

    private function getContext(): ?AdminContext
    {
        return $this->get(AdminContextProvider::class)->getContext();
    }


    private function ajaxEdit(EntityDto $entityDto, ?string $propertyName, bool $newValue): AfterCrudActionEvent
    {
        if (!$entityDto->hasProperty($propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" boolean field cannot be changed because it doesn\'t exist in the "%s" entity.', $propertyName, $entityDto->getName()));
        }

        $this->get(EntityUpdater::class)->updateProperty($entityDto, $propertyName, $newValue);

        $event = new BeforeEntityUpdatedEvent($entityDto->getInstance());
        $this->get('event_dispatcher')->dispatch($event);
        $entityInstance = $event->getEntityInstance();

        $this->updateEntity($this->get('doctrine')->getManagerForClass($entityDto->getFqcn()), $entityInstance);

        $this->get('event_dispatcher')->dispatch(new AfterEntityUpdatedEvent($entityInstance));

        $entityDto->setInstance($entityInstance);

        $parameters = KeyValueStore::new([
            'action' => Action::EDIT,
            'entity' => $entityDto,
        ]);

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->get('event_dispatcher')->dispatch($event);

        return $event;
    }

    public function edit(AdminContext $context)
    {
        $event = new BeforeCrudActionEvent($context);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION)) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $this->get(EntityFactory::class)->processFields($context->getEntity(), FieldCollection::new($this->configureFields(Crud::PAGE_EDIT)));
        $this->get(EntityFactory::class)->processActions($context->getEntity(), $context->getCrud()->getActionsConfig());
        $entityInstance = $context->getEntity()->getInstance();

        if ($context->getRequest()->isXmlHttpRequest()) {
            $fieldName = $context->getRequest()->query->get('fieldName');
            $newValue = 'true' === mb_strtolower($context->getRequest()->query->get('newValue'));

            $event = $this->ajaxEdit($context->getEntity(), $fieldName, $newValue);
            if ($event->isPropagationStopped()) {
                return $event->getResponse();
            }

            // cast to integer instead of string to avoid sending empty responses for 'false'
            return new Response((int) $newValue);
        }

        $editForm = $this->createEditForm($context->getEntity(), $context->getCrud()->getEditFormOptions(), $context);
        $editForm->handleRequest($context->getRequest());

        if ($editForm->isSubmitted() && $editForm->isValid()) {


            $event = new BeforeEntityUpdatedEvent($entityInstance);
            $this->get('event_dispatcher')->dispatch($event);
            $entityInstance = $event->getEntityInstance();

            $requestData = $context->getRequest()->request->get('Magazine');

            if (isset($requestData['is_image_changed']) === true and $requestData['is_image_changed'] === 'true') {
                $imageFile = $editForm->get('image')->getData();
                $realPathOfImg = '';

                $finder = new Finder();
                $finder->files()->in($_SERVER['DOCUMENT_ROOT'] . '/images/magazines/');
                $finder->name($requestData['image_name']);
                $iterator = $finder->getIterator();
                $iterator->rewind();
                $firstFile = $iterator->current();

                if ($firstFile !== null) {
                    $realPathOfImg = '/images/magazines/' . $firstFile->getRelativePathname();
                } else {
                    if ($imageFile) {
                        $newFilename = 'magazine_'.$entityInstance->getUuid(). '.' . $imageFile->guessExtension();
                        // Move the file to the directory where brochures are stored
                        try {
                            $imageFile->move(
                                $this->getParameter('magazineImagesDirectory'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                            // ... handle exception if something happens during file upload
                        }
                        $realPathOfImg = '/images/magazines/' . $newFilename;
                    }
                }
                $entityInstance->setImageUrl($realPathOfImg);

            }

            if (isset($requestData['authorsSelected']) === true) {

                if (count($entityInstance->getAuthors()) > 0) {
                    foreach ($entityInstance->getAuthors() as $author) {
                        $entityInstance->removeAuthor($author);
                    }
                }

                $authorRepo = $this->getDoctrine()->getRepository(Author::class);

                if (count($requestData['authorsSelected']) > 0) {
                    foreach ($requestData['authorsSelected'] as $item) {
                        $existAuthor = $authorRepo->find((int)$item);
                        if ($existAuthor !== null) {
                            $entityInstance->addAuthor($existAuthor);
                        }
                    }
                }

            }

            $this->updateEntity($this->get('doctrine')->getManagerForClass($context->getEntity()->getFqcn()), $entityInstance);

            $this->get('event_dispatcher')->dispatch(new AfterEntityUpdatedEvent($entityInstance));

            $submitButtonName = $context->getRequest()->request->get('ea')['newForm']['btn'];
            if (Action::SAVE_AND_CONTINUE === $submitButtonName) {
                $url = $this->get(CrudUrlGenerator::class)->build()
                    ->setAction(Action::EDIT)
                    ->setEntityId($context->getEntity()->getPrimaryKeyValue())
                    ->generateUrl();

                return $this->redirect($url);
            }

            if (Action::SAVE_AND_RETURN === $submitButtonName) {
                $url = $context->getReferrer()
                    ?? $this->get(CrudUrlGenerator::class)->build()->setAction(Action::INDEX)->generateUrl();

                return $this->redirect($url);
            }

            return $this->redirectToRoute($context->getDashboardRouteName());
        }

        $responseParameters = $this->configureResponseParameters(KeyValueStore::new([
            'pageName' => Crud::PAGE_EDIT,
            'templateName' => 'crud/edit',
            'edit_form' => $editForm,
            'entity' => $context->getEntity(),
        ]));

        $event = new AfterCrudActionEvent($context, $responseParameters);
        $this->get('event_dispatcher')->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }

        return $responseParameters;
    }
}
