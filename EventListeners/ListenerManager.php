<?php
/*************************************************************************************/
/*                                                                                   */
/*      This file is not free software                                               */
/*                                                                                   */
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*************************************************************************************/

/**
 * Created by Franck Allimant, CQFDev <franck@cqfdev.fr>
 * Date: 20/12/2015 20:25
 */

namespace PaniersAbandonnes\EventListeners;

use PaniersAbandonnes\Events\PaniersAbandonnesEvent;
use PaniersAbandonnes\Model\PanierAbandonne;
use PaniersAbandonnes\Model\PanierAbandonneQuery;
use PaniersAbandonnes\PaniersAbandonnes;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Thelia\Core\Event\ActionEvent;
use Thelia\Core\Event\Cart\CartDuplicationEvent;
use Thelia\Core\Event\Cart\CartEvent;
use Thelia\Core\Event\Cart\CartPersistEvent;
use Thelia\Core\Event\Cart\CartRestoreEvent;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Security\SecurityContext;
use Thelia\Log\Tlog;
use Thelia\Mailer\MailerFactory;
use Thelia\Model\Cart;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Customer;

class ListenerManager implements EventSubscriberInterface
{
    /** @var  SecurityContext */
    protected $securityContext;

    /** @var  RequestStack */
    protected $requestStack;

    /** @var  MailerFactory */
    protected $mailer;

    /**
     * ListenerManager constructor.
     * @param SecurityContext $securityContext
     * @param RequestStack $requestStack
     * @param MailerFactory $mailer
     */
    public function __construct(SecurityContext $securityContext, RequestStack $requestStack, MailerFactory $mailer)
    {
        $this->securityContext = $securityContext;
        $this->requestStack = $requestStack;
        $this->mailer = $mailer;
    }

    public function detectCustomerEmailFromRequest()
    {
        $request = $this->requestStack->getCurrentRequest();

        $source = $request->get('utm_source');

        if ($source == 'mail' && null !== $email = $request->get('mail')) {
            $request->getSession()->set('utm_source_email', $email);;
        }
    }

    /**
     * @return array
     */
    protected function getCustomerEmailAndLocale()
    {
        /** @var Customer $customer */
        if (null !== $customer = $this->securityContext->getCustomerUser()) {
            return [ 'email' => $customer->getEmail(), 'locale' => $customer->getCustomerLang()->getLocale() ];
        }

        /** @var Session $session */
        $session = $this->requestStack->getCurrentRequest()->getSession();
        $email = $session->get('utm_source_email');

        if (null !== $email) {
            return [ 'email' => $email, 'locale' => $session->getLang()->getLocale() ];
        }

        return null;
    }

    /**
     * @param Cart $cart
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function storeCart(Cart $cart)
    {
        // Si le panier n'est pas trop vieux, on le stocke
        if ($this->isStorable($cart)) {
            if (null !== $data = $this->getCustomerEmailAndLocale()) {
                // Supprimer tous les paniers relatifs à ce client
                PanierAbandonneQuery::create()
                    ->filterByEmailClient($data['email'], Criteria::LIKE)
                    ->delete();

                // Enregistrer le nouveau panier.
                (new PanierAbandonne())
                    ->setCartId($cart->getId())
                    ->setEmailClient($data['email'])
                    ->setLocale($data['locale'])
                    ->setLoginToken(uniqid())
                    ->setLastUpdate(new \DateTime())
                    ->save();
            }
        }
    }

    /**
     * @param CartRestoreEvent $event
     * @throws \Exception
     */
    public function restoreCart(CartRestoreEvent $event)
    {
        $this->storeCart($event->getCart());
    }

    /**
     * @param CartPersistEvent $event
     * @throws \Exception
     */
    public function persistCart(CartPersistEvent $event)
    {
        $this->storeCart($event->getCart());
    }

    /**
     * @param CartDuplicationEvent $event
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function duplicateCart(CartDuplicationEvent $event)
    {
        $originalCart = $event->getOriginalCart();

        // Supprimer le vieux panier
        if (null !== $pa = PanierAbandonneQuery::create()->findOneByCartId($originalCart->getId())) {
            $pa->delete();
        }

        // Ne pas stocker un vieux panier qui serait dupliqué
        if ($this->isStorable($originalCart)) {
            $this->storeCart($event->getDuplicatedCart());
        }
    }

    /**
     * @param Cart $cart
     * @return bool
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function isStorable(Cart $cart)
    {
        if (! empty($cart) && $cart->getId() > 0 && $cart->countCartItems() > 0) {
            $delaiSecondRappel = new \DateTime();

            $delaiSecondRappel
                ->add(
                    new \DateInterval(
                        'PT' . PaniersAbandonnes::getConfigValue(PaniersAbandonnes::VAR_DELAI_RAPPEL_2) . 'M'
                    )
                );

            // La panier est obsolete s'il existe depuis plus longtemps que le délai d'envoi du 2eme rappel.
            return $cart->getCreatedAt() < $delaiSecondRappel;
        }

        return false;
    }

    /**
     * @param CartEvent $event
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function updateCart(CartEvent $event)
    {
        // Mettre à jour le champ UpdatedAt
        if ($this->isStorable($event->getCart())) {
            if (null !== $pa = PanierAbandonneQuery::create()->findOneByCartId($event->getCart()->getId())) {
                $pa->setLastUpdate(new \DateTime())->save();
            }
        }
    }

    /**
     * @param OrderEvent $event
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function orderStatusUpdate(OrderEvent $event)
    {
        // Si la commande est payée, supprimer le panier associé.
        $order = $event->getOrder();

        if ($order->isPaid()) {
            if (null !== $pa = PanierAbandonneQuery::create()->findOneByCartId($order->getCartId())) {
                $pa->delete();
            }
        }
    }

    /**
     * @param ActionEvent $event
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function cron(ActionEvent $event)
    {
        Tlog::getInstance()->notice("Examen des paniers abandonnes");

        $this->envoyerRappels(
            PaniersAbandonnes::VAR_DELAI_RAPPEL_1,
            PanierAbandonne::RAPPEL_PAS_ENVOYE,
            PaniersAbandonnes::MESSAGE_RAPPEL_1,
            PanierAbandonne::RAPPEL_1_ENVOYE
        );

        $this->envoyerRappels(
            PaniersAbandonnes::VAR_DELAI_RAPPEL_2,
            PanierAbandonne::RAPPEL_1_ENVOYE,
            PaniersAbandonnes::MESSAGE_RAPPEL_2,
            PanierAbandonne::RAPPEL_2_ENVOYE
        );

        // Supprimer les entrées auxquelles on a envoyé le 2 eme rappel
        PanierAbandonneQuery::create()
            ->filterByEtatRappel(PanierAbandonne::RAPPEL_2_ENVOYE)
            ->delete()
        ;
    }

    /**
     * @param $varDelai
     * @param $filtreEtatRappel
     * @param $codeMessage
     * @param $nouvelEtat
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function envoyerRappels($varDelai, $filtreEtatRappel, $codeMessage, $nouvelEtat)
    {
        $delai = new \DateTime();
        $delai = $delai->sub(new \DateInterval('PT' . PaniersAbandonnes::getConfigValue($varDelai) . 'M'));

        $panierAbandonnes = PanierAbandonneQuery::create()
            ->filterByEtatRappel($filtreEtatRappel)
            ->filterByLastUpdate($delai, Criteria::LESS_THAN)
            ->find();

        /** @var PanierAbandonne $panierAbandonne */
        foreach ($panierAbandonnes as $panierAbandonne) {
            // Vérifier que le cart n'est pas vide.
            if ($panierAbandonne->getCart()->countCartItems() > 0) {
                try {
                    $this->mailer->sendEmailMessage(
                        $codeMessage,
                        [ConfigQuery::getStoreEmail() => ConfigQuery::getStoreName()],
                        [$panierAbandonne->getEmailClient() => $panierAbandonne->getEmailClient()],
                        [
                            'cart_id' => $panierAbandonne->getCartId(),
                            'login_token' => $panierAbandonne->getLoginToken(),
                            'code_promo' => PaniersAbandonnes::getConfigValue(PaniersAbandonnes::VAR_CODE_PROMO_RAPPEL_2)
                        ],
                        $panierAbandonne->getLocale()
                    );
                    Tlog::getInstance()->notice("Envoi du rappel no. " . $nouvelEtat . " au client " . $panierAbandonne->getEmailClient());
                } catch (\Exception $ex) {
                    Tlog::getInstance()->error("Echec de l'envoi du rappel no. " . $nouvelEtat . " au client " . $panierAbandonne->getEmailClient() . ". Raison:".$ex->getMessage());
                }

                $panierAbandonne->clearAllReferences();

                $panierAbandonne
                    ->setEtatRappel($nouvelEtat)
                    ->save();
            } else {
                // Supprimer ce panier obsolète
                $panierAbandonne->delete();
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [ 'detectCustomerEmailFromRequest', 100 ],

            TheliaEvents::CART_PERSIST => ['persistCart', 100],
            TheliaEvents::CART_RESTORE_CURRENT => ['restoreCart', 100],
            TheliaEvents::CART_DUPLICATE  => ['duplicateCart', 100],

            TheliaEvents::CART_ADDITEM => [ 'updateCart', 100 ],
            TheliaEvents::CART_DELETEITEM => [ 'updateCart', 100 ],
            TheliaEvents::CART_UPDATEITEM => [ 'updateCart', 100 ],

            TheliaEvents::ORDER_UPDATE_STATUS => [ 'orderStatusUpdate', 100 ],

            PaniersAbandonnesEvent::EXAMINER_PANIERS_EVENT => [ 'cron', 100 ]
        ];
    }
}
