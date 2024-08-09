<?php

namespace FlashPay;

use Bitrix\Sale\Tax;
use FlashPayException;

class FlashPayMerchantAPI extends Tax
{
    public const LOG_TYPE_WEBHOOK              = 'webhook';
    public const LOG_TYPE_WEBHOOK_WRONG_AMOUNT = 'webhook_wrong_amount';

    protected const URL_PROD  = 'https://pay.flashpay.kg';
    protected const URL_STAGE = 'https://pay-stage.flashpay.kg';

    protected $isProdMode;
    protected $language;
    protected $apiUrl;
    protected $projectId;
    protected $secretKey;
    protected $error = '';

    /**
     * Constructor
     *
     * @param array $params Configs of module
     */
    public function __construct(array $params)
    {
        $this->isProdMode = 'Y' === $params['IS_FLASHPAY_PROD_MODE'];
        $this->language = $params['LANGUAGE_PAYMENT'] ?: 'en';

        if ($this->isProdMode) {
            $this->apiUrl = static::URL_PROD;
            $this->projectId = $params['PROJECT_ID_PROD'];
            $this->secretKey = $params['SECRET_KEY_PROD'];
        } else {
            $this->apiUrl = static::URL_STAGE;
            $this->projectId = $params['PROJECT_ID_STAGE'];
            $this->secretKey = $params['SECRET_KEY_STAGE'];
        }

        parent::__construct();
    }

    /**
     * @return string
     */
    public function getCurrentProjectId(): string
    {
        return $this->projectId;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Request to PP as GET request without init request to FlashPay
     *
     * @param array $paymentData
     * @return array
     */
    public function createPaymentPageRequest(array $paymentData): array
    {
        $body = json_encode($paymentData);

        return [
            'action_url' => rtrim($this->apiUrl, '/') . '/payment',
            'language'   => $this->language,
            'body'       => $body,
            'signature'  => $this->getSignatureOfString($body),
        ];
    }

    /**
     * @param string|null $paymentId
     * @param string|null $type
     * @param string $input
     * @param string $response
     * @param string $message
     * @return void
     */
    public static function logData(
        ?string $paymentId = null,
        ?string $type = null,
        string $input = '',
        string $response = '',
        string $message = ''
    ): void {
        global $DB;

        $arFields = [
            'payment_id' => $paymentId,
            'type'       => $type,
            'input'      => $input,
            'response'   => $response,
            'message'    => $message,
        ];

        $arInsert = $DB->PrepareInsert('flashpay_logs', $arFields);

        $strSql = "INSERT INTO flashpay_logs (" . $arInsert[0] . ") " . "VALUES(" . $arInsert[1] . ")";
        $DB->Query($strSql, false, "File: " . __FILE__ . ", Line: " . __LINE__);
    }

    /**
     * @param string $data
     * @return string
     */
    protected function getSignatureOfString(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secretKey);
    }
}
