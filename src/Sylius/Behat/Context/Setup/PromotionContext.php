<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Doctrine\Common\Persistence\ObjectManager;
use Sylius\Component\Core\Factory\RuleFactoryInterface;
use Sylius\Component\Core\Test\Factory\TestPromotionFactoryInterface;
use Sylius\Component\Core\Test\Services\SharedStorageInterface;
use Sylius\Component\Promotion\Factory\ActionFactoryInterface;
use Sylius\Component\Promotion\Repository\PromotionRepositoryInterface;

/**
 * @author Mateusz Zalewski <mateusz.zalewski@lakion.com>
 */
final class PromotionContext implements Context
{
    /**
     * @var SharedStorageInterface
     */
    private $sharedStorage;

    /**
     * @var ActionFactoryInterface
     */
    private $actionFactory;

    /**
     * @var RuleFactoryInterface
     */
    private $ruleFactory;

    /**
     * @var TestPromotionFactoryInterface
     */
    private $testPromotionFactory;

    /**
     * @var PromotionRepositoryInterface
     */
    private $promotionRepository;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @param SharedStorageInterface $sharedStorage
     * @param ActionFactoryInterface $actionFactory
     * @param RuleFactoryInterface $ruleFactory
     * @param TestPromotionFactoryInterface $testPromotionFactory
     * @param PromotionRepositoryInterface $promotionRepository
     * @param ObjectManager $objectManager
     */
    public function __construct(
        SharedStorageInterface $sharedStorage,
        ActionFactoryInterface $actionFactory,
        RuleFactoryInterface $ruleFactory,
        TestPromotionFactoryInterface $testPromotionFactory,
        PromotionRepositoryInterface $promotionRepository,
        ObjectManager $objectManager
    ) {
        $this->sharedStorage = $sharedStorage;
        $this->actionFactory = $actionFactory;
        $this->ruleFactory = $ruleFactory;
        $this->testPromotionFactory = $testPromotionFactory;
        $this->promotionRepository = $promotionRepository;
        $this->objectManager = $objectManager;
    }

    /**
     * @Given there is a promotion :promotionName
     */
    public function thereIsPromotion($promotionName)
    {
        $promotion = $this->testPromotionFactory
            ->createForChannel($promotionName, $this->sharedStorage->get('channel'))
        ;

        $this->promotionRepository->add($promotion);
        $this->sharedStorage->set('promotion', $promotion);
    }

    /**
     * @Given /^(?:it|the promotion) gives "(?:€|£|\$)([^"]+)" fixed discount to every order$/
     */
    public function itGivesFixedDiscountToEveryOrder($amount)
    {
        $currentPromotion = $this->sharedStorage->get('promotion');

        $action = $this->actionFactory->createFixedDiscount($this->getPriceFromString($amount));
        $currentPromotion->addAction($action);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(?:it|the promotion) gives "([^"]+)%" percentage discount to every order$/
     */
    public function itGivesPercentageDiscountToEveryOrder($discount)
    {
        $currentPromotion = $this->sharedStorage->get('promotion');

        $action = $this->actionFactory->createPercentageDiscount($this->getPercentageFromString($discount));
        $currentPromotion->addAction($action);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(?:it|the promotion) gives "(?:€|£|\$)([^"]+)" fixed discount to every order with quantity at least ([^"]+)$/
     */
    public function itGivesFixedDiscountToEveryOrderWithQuantityAtLeast($amount, $quantity)
    {
        $currentPromotion = $this->sharedStorage->get('promotion');

        $action = $this->actionFactory->createFixedDiscount($this->getPriceFromString($amount));
        $currentPromotion->addAction($action);

        $rule = $this->ruleFactory->createCartQuantity((int) $quantity);
        $currentPromotion->addRule($rule);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(?:it|the promotion) gives "(?:€|£|\$)([^"]+)" fixed discount to every order with items total at least "(?:€|£|\$)([^"]+)"$/
     */
    public function itGivesFixedDiscountToEveryOrderWithItemsTotalAtLeast($amount, $targetAmount)
    {
        $currentPromotion = $this->sharedStorage->get('promotion');

        $action = $this->actionFactory->createFixedDiscount($this->getPriceFromString($amount));
        $currentPromotion->addAction($action);

        $rule = $this->ruleFactory->createItemTotal($this->getPriceFromString($targetAmount));
        $currentPromotion->addRule($rule);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(?:it|the promotion) gives "([^"]+)%" percentage discount on shipping to every order$/
     */
    public function itGivesPercentageDiscountOnShippingToEveryOrder($discount)
    {
        $currentPromotion = $this->sharedStorage->get('promotion');

        $action = $this->actionFactory->createPercentageShippingDiscount($this->getPercentageFromString($discount));
        $currentPromotion->addAction($action);

        $this->objectManager->flush();
    }

    /**
     * @param string $price
     *
     * @return int
     */
    private function getPriceFromString($price)
    {
        return (int) round(($price * 100), 2);
    }

    /**
     * @param string $discount
     *
     * @return float
     */
    private function getPercentageFromString($discount)
    {
        return ((int) $discount) / 100;
    }
}
