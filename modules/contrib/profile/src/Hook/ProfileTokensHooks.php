<?php

namespace Drupal\profile\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\user\UserInterface;

/**
 * Token hook implementations for Profile.
 */
class ProfileTokensHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new ProfileTokensHooks object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Token $token,
    protected RendererInterface $renderer,
  ) {}

  /**
   * Implements hook_token_info_alter().
   */
  #[Hook('token_info_alter')]
  public function tokenInfoAlter(array &$info): void {
    // Register a token for each profile type on the user object.
    foreach ($this->entityTypeManager->getStorage('profile_type')->loadMultiple() as $profile_type) {
      $args = ['@type' => $profile_type->get('label')];
      $token_name = $profile_type->get('id');
      $info['tokens']['user'][$token_name] = [
        'name' => $this->t('@type profile', $args),
        'description' => $this->t('Profile of the type "@type" associate with a user.', $args),
        'type' => 'profile',
      ];
    }
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens($type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    $replacements = [];
    // Entity tokens on the Drupal user object.
    if ($type == 'entity' && !empty($data['entity_type']) && !empty($data['entity']) && !empty($data['token_type'])) {
      $user = $data['entity'];
      if (!($user instanceof UserInterface)) {
        return $replacements;
      }

      $profile_type_ids = array_keys($this->entityTypeManager->getStorage('profile_type')->loadMultiple());
      foreach ($tokens as $name => $original) {
        // Split the token name to see if it is a profile type.
        $parts = explode(':', $name);

        // Determine if this token represents a profile type.
        if (!empty($parts[0]) && in_array($parts[0], $profile_type_ids)) {
          $profile_type = $parts[0];

          /** @var \Drupal\profile\ProfileStorageInterface $storage */
          $storage = $this->entityTypeManager->getStorage('profile');
          $profile = $storage->loadByUser($user, $profile_type);

          if (!empty($profile)) {
            // If the token contains prefixes, invoke the field token
            // generators.
            if (count($parts) > 1 && $profile_tokens = $this->token->findWithPrefix($tokens, $profile_type)) {
              $profile_token_data = [
                'entity' => $profile,
                'entity_type' => 'profile',
                'token_type' => 'profile',
              ];

              // Invoke the generators to resolve the profile token.
              $replacements += $this->token->generate('entity', $profile_tokens, $profile_token_data, $options, $bubbleable_metadata);
            }
            elseif (count($parts) == 1) {
              // Render the full profile.
              $view_builder = $this->entityTypeManager->getViewBuilder('profile');
              $profile_view = $view_builder->view($profile, 'token');
              $replacements[$original] = $this->renderer->renderRoot($profile_view);
            }
          }
        }
      }
    }

    return $replacements;
  }

}
