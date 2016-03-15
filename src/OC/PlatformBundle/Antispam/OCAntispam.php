<?php
// src/OC/PlatformBundle/Antispam/OCAntispam.php

namespace OC\PlatformBundle\Antispam;

class OCAntispam extends \Twig_Extension
{
  private $mailer;
  private $locale;
  private $minLength;

  public function __construct(\Swift_Mailer $mailer, $minLength)
  {
    $this->mailer    = $mailer;
    $this->minLength = (int) $minLength;
  }

  // Et on ajoute un setter
  public function setLocale($locale)
  {
    $this->locale = $locale;
  }

  /**
   * Vérifie si le texte est un spam ou non
   *
   * @param string $text
   * @return bool
   */
  public function isSpam($text)
  {
    return strlen($text) < $this->minLength;
  }

  // Twig va exécuter cette méthode pour savoir quelle(s) fonction(s) ajoute notre service
  public function getFunctions()
  {
    return array(
      'checkIfSpam' => new \Twig_Function_Method($this, 'isSpam')
    );
  }

  // La méthode getName() identifie votre extension Twig, elle est obligatoire
  public function getName()
  {
    return 'OCAntispam';
  }

}