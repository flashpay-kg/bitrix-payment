<?php

use FlashPay\FlashPayMerchantAPI;

class FlashPayNotification extends FlashPayMerchantAPI
{
    protected const STATUS_SUCCESS = 'success';
    protected const STATUS_DECLINE = 'decline';

    /** @var string */
    private $paymentStatus;

    /**
     * Constructor
     *
     * @param array $params
     * @param array $webhookData
     * @param string $rawInput
     * @param string $inputSignature
     *
     * @throws FlashPayException
     */
    public function __construct(array $params, array $webhookData, string $rawInput, string $inputSignature)
    {
        parent::__construct($params);

        if (!isset($params['IS_FLASHPAY_PROD_MODE'])) {
            throw new FlashPayException('It is not FlashPay pay system or mode is not selected/saved');
        }

        $this->checkNotification($params, $webhookData, $rawInput, $inputSignature);

        $this->paymentStatus = $webhookData['operation']['status'];
    }

    /**
     * @return bool
     */
    public function isPaymentSuccess(): bool
    {
        return static::STATUS_SUCCESS === strtolower($this->paymentStatus);
    }

    /**
     * @return bool
     */
    public function isPaymentDecline(): bool
    {
        return static::STATUS_DECLINE === strtolower($this->paymentStatus);
    }

    /**
     * Checks is request data valid
     *
     * @param array $moduleParams
     * @param array $webhookData
     * @param string $rawInput
     * @param string $inputSignature
     *
     * @return void
     * @throws FlashPayException
     */
    protected function checkNotification(
        array $moduleParams,
        array $webhookData,
        string $rawInput,
        string $inputSignature
    ): void {
        $validationErrorMsg = [];

        // check for set values
        if (empty($webhookData['project_id'])) {
            $validationErrorMsg[] = 'project_id is empty';
        }

        if (empty($webhookData['order_id'])) {
            $validationErrorMsg[] = 'order_id is empty';
        }

        if (empty($webhookData['transaction_id'])) {
            $validationErrorMsg[] = 'transaction_id is empty';
        }

        if (empty($webhookData['operation']['id'])) {
            $validationErrorMsg[] = 'operation.id is empty';
        }

        if (empty($webhookData['operation']['amount'])) {
            $validationErrorMsg[] = 'operation.amount is empty';
        }

        if (empty($webhookData['operation']['currency'])) {
            $validationErrorMsg[] = 'operation.currency is empty';
        }

        if (empty($webhookData['operation']['status'])) {
            $validationErrorMsg[] = 'operation.status is empty';
        }

        if (!empty($validationErrorMsg)) {
            throw new FlashPayException('Failure of validation: ' . implode('; ', $validationErrorMsg));
        }

        // check correct current project_id
        if ($webhookData['project_id'] !== $this->projectId) {
            $projectIdError = 'Wrong project_id value';

            if ($webhookData['project_id'] === $moduleParams['PROJECT_ID_PROD']) {
                $projectIdError .= '. It is value for production mode, but different mode enabled';
            } elseif ($webhookData['project_id'] === $moduleParams['PROJECT_ID_STAGE']) {
                $projectIdError .= '. It is value for sandbox mode, but different mode enabled';
            } else {
                $projectIdError .= '. No such value in configurations';
            }

            throw new FlashPayException($projectIdError);
        }

        // check for signature
        if (empty($inputSignature)) {
            throw new FlashPayException('Empty input signature');
        }

        $innerSignature = $this->getSignatureOfString($rawInput);

        if ($innerSignature !== $inputSignature) {
            throw new FlashPayException('Incorrect signature');
        }
    }
}
