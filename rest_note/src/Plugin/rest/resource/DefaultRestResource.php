<?php

namespace Drupal\rest_note\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest_note\Entity\NoteInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "default_rest_resource",
 *   label = @Translation("Default rest resource"),
 *   serialization_class = "Drupal\rest_note\Entity\Note",
 *   uri_paths = {
 *     "canonical" = "/api/rest/{note}",
 *     "https://www.drupal.org/link-relations/create" = "/api/rest/note"
 *   }
 * )
 */
class DefaultRestResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest_note'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param \Drupal\rest_note\Entity\NoteInterface $entity
   *   The entity object.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the entity with its accessible fields.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get(NoteInterface $entity) {
    $entity_access = $entity->access('view', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      throw new AccessDeniedHttpException();
    }

    $response = new ResourceResponse($entity, 200);
    $response->addCacheableDependency($entity);
    $response->addCacheableDependency($entity_access);
    foreach ($entity as $field_name => $field) {
      /** @var \Drupal\Core\Field\FieldItemListInterface $field */
      $field_access = $field->access('view', NULL, TRUE);
      $response->addCacheableDependency($field_access);

      if (!$field_access->isAllowed()) {
        $entity->set($field_name, NULL);
      }
    }

    return $response;
  }

  /**
   * Responds to POST requests.
   *
   * @param \Drupal\rest_note\Entity\NoteInterface $entity
   *   The entity.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post(NoteInterface $entity = NULL) {
    if ($entity == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }

    if (!$entity->access('create')) {
      throw new AccessDeniedHttpException();
    }

    // POSTed entities must not have an ID set, because we always want to create
    // new entities here.
    if (!$entity->isNew()) {
      throw new BadRequestHttpException('Only new entities can be created');
    }

    try {
      $entity->save();
      $this->logger->notice('Created note with name %name.', array('%name' => $entity->getName()));

      // 201 Created responses return the newly created entity in the response
      // body.
      $url = $entity->urlInfo('canonical', ['absolute' => TRUE])->toString(TRUE);
      $response = new ResourceResponse($entity, 201, ['Location' => $url->getGeneratedUrl()]);
      // Responses after creating an entity are not cacheable, so we add no
      // cacheability metadata here.
      return $response;
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

  /**
   * Responds to UPDATE requests.
   *
   * @param \Drupal\rest_note\Entity\NoteInterface $original_entity
   *   The original entity object.
   * @param \Drupal\rest_note\Entity\NoteInterface $entity
   *   The entity.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function patch(NoteInterface $original_entity, NoteInterface $entity = NULL) {
    if ($entity == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }

    if (!$original_entity->access('update')) {
      throw new AccessDeniedHttpException();
    }

    // Overwrite the received properties.
    $entity_keys = $entity->getEntityType()->getKeys();
    foreach ($entity->_restSubmittedFields as $field_name) {
      $field = $entity->get($field_name);

      // Entity key fields need special treatment: together they uniquely
      // identify the entity. Therefore it does not make sense to modify any of
      // them. However, rather than throwing an error, we just ignore them as
      // long as their specified values match their current values.
      if (in_array($field_name, $entity_keys, TRUE)) {
        // Unchanged values for entity keys don't need access checking.
        if ($original_entity->get($field_name)->getValue() === $entity->get($field_name)->getValue()) {
          continue;
        }
        // It is not possible to set the language to NULL as it is automatically
        // re-initialized. As it must not be empty, skip it if it is.
        elseif (isset($entity_keys['langcode']) && $field_name === $entity_keys['langcode'] && $field->isEmpty()) {
          continue;
        }
      }

      if (!$original_entity->get($field_name)->access('edit')) {
        throw new AccessDeniedHttpException("Access denied on updating field '$field_name'.");
      }
      $original_entity->set($field_name, $field->getValue());
    }

    try {
      $original_entity->save();
      $this->logger->notice('Updated note %name.', array('%name' => $original_entity->getName()));

      // Update responses have an empty body.
      return new ResourceResponse(NULL, 204);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

  /**
   * Responds to entity DELETE requests.
   *
   * @param \Drupal\rest_note\Entity\NoteInterface $entity
   *   The entity object.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function delete(NoteInterface $entity) {
    if (!$entity->access('delete')) {
      throw new AccessDeniedHttpException();
    }
    try {
      $entity->delete();
      $this->logger->notice('Deleted note %name.', array('%name' => $entity->getName()));

      // Delete responses have an empty body.
      return new ResourceResponse(NULL, 204);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);

    $parameters = $route->getOption('parameters') ?: array();

    $parameters['note']['type'] = 'entity:note';
    $route->setOption('parameters', $parameters);

    return $route;
  }

}
