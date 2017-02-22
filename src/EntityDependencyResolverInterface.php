<?php

namespace Drupal\content_deploy;

/**
 * The resolver for entity dependencies.
 */
interface EntityDependencyResolverInterface {

  /**
   * Gets the depended entity from the dependency name.
   *
   * @code
   * // A dependency to the content entity.
   * ['content' => 'taxonomy_term:tags:97370a5d-ea40-499d-a9a2-59f02d2820dc']
   *
   * // A dependency to the config entity.
   * ['config' => 'user.user.97370a5d-ea40-499d-a9a2-59f02d2820dc']
   * @endcode
   *
   * @param string $dep_name
   *   The dependency name.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|string
   *   The resolved entity instance.
   *
   * @throws \Drupal\content_deploy\Exception\MissingDependencyException
   *   Cannot resolve the dependency.
   */
  public function resolveEntityDependency($dep_name);

}
