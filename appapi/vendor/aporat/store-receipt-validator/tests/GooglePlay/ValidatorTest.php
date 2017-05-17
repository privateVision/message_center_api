<?php

namespace ReceiptValidator\GooglePlay;

/**
 * @group library
 */
class GooglePlayValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testValidate()
    {
        $package = 'testPackage';
        $productId = '15';
        $purchaseToken = 'testPurchaseToken';

        // mock objects
        $productResponseMock = $this->getMockBuilder('\Google_Service_AndroidPublisher_ProductPurchase')
            ->disableOriginalConstructor()->getMock();
        $subscriptionResponseMock = $this->getMockBuilder('\Google_Service_AndroidPublisher_SubscriptionPurchase')
            ->disableOriginalConstructor()->getMock();
        $googleServiceAndroidPublisherMock = $this->getMockBuilder('\Google_Service_AndroidPublisher')
            ->disableOriginalConstructor()->getMock();
        $productPurchaseMock = $this->getMockBuilder('\Google_Service_AndroidPublisher_Resource_PurchasesProducts')
            ->disableOriginalConstructor()->getMock();
        $subscriptionPurchaseMock = $this->getMockBuilder('\Google_Service_AndroidPublisher_Resource_PurchasesSubscriptions')
            ->disableOriginalConstructor()->getMock();

        // mock expectations
        $googleServiceAndroidPublisherMock->purchases_products = $productPurchaseMock;
        $googleServiceAndroidPublisherMock->purchases_subscriptions = $subscriptionPurchaseMock;

        $productPurchaseMock->expects($this->once())->method('get')
            ->with($package, $productId, $purchaseToken)->willReturn($productResponseMock);

        $subscriptionPurchaseMock->expects($this->once())->method('get')
            ->with($package, $productId, $purchaseToken)->willReturn($subscriptionResponseMock);

        $googlePlayValidator = (new Validator($googleServiceAndroidPublisherMock))
            ->setPackageName($package)
            ->setProductId($productId)
            ->setPurchaseToken($purchaseToken);

        $this->assertEquals(new PurchaseResponse($productResponseMock), $googlePlayValidator->validatePurchase());
        $this->assertEquals(new SubscriptionResponse($subscriptionResponseMock), $googlePlayValidator
            ->setValidationModePurchase(false)->validateSubscription()
        );
    }
}
