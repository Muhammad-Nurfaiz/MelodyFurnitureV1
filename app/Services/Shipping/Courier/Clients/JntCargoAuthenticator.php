<?php

namespace App\Services\Shipping\Courier\Clients;

use RuntimeException;

class JntCargoAuthenticator
{
    protected array $config;

    public function __construct()
    {
        $this->config = config(
            'shipping.couriers.jnt_cargo',
            []
        );
    }

    /**
     * Generate Header Signature.
     *
     * J&T Cargo:
     *
     * digest = Base64(MD5(bizContent + privateKey))
     */
    public function generateHeaderDigest(
        string $bizContent
    ): string {

        $privateKey = $this->config['private_key'] ?? null;

        if (blank($privateKey)) {
            throw new RuntimeException(
                'J&T Cargo PrivateKey belum dikonfigurasi.'
            );
        }

        return base64_encode(
            md5(
                $bizContent . $privateKey,
                true
            )
        );
    }

    /**
     * Generate Body Signature.
     *
     * J&T Cargo:
     *
     * cipherText =
     * strtoupper(
     *     MD5(plaintextPassword + "jadada2362t")
     * )
     *
     * digest =
     * Base64(
     *     MD5(
     *         customerCode
     *         + cipherText
     *         + privateKey
     *     )
     * )
     */
    public function generateBodyDigest(): string
    {
        $customerCode =
            $this->config['customer_code'] ?? null;

        $customerPassword =
            $this->config['customer_password'] ?? null;

        $privateKey =
            $this->config['private_key'] ?? null;

        if (blank($customerCode)) {
            throw new RuntimeException(
                'J&T Cargo CustomerCode belum dikonfigurasi.'
            );
        }

        if (blank($customerPassword)) {
            throw new RuntimeException(
                'J&T Cargo Customer Password belum dikonfigurasi.'
            );
        }

        if (blank($privateKey)) {
            throw new RuntimeException(
                'J&T Cargo PrivateKey belum dikonfigurasi.'
            );
        }

        $cipherText = strtoupper(
            md5(
                $customerPassword . 'jadada236t2'
            )
        );

        return base64_encode(
            md5(
                $customerCode
                . $cipherText
                . $privateKey,
                true
            )
        );
    }

    /**
     * Get API Account.
     */
    public function apiAccount(): string
    {
        $apiAccount =
            $this->config['api_account'] ?? null;

        if (blank($apiAccount)) {
            throw new RuntimeException(
                'J&T Cargo ApiAccount belum dikonfigurasi.'
            );
        }

        return (string) $apiAccount;
    }

    /**
     * Get Customer Code.
     */
    public function customerCode(): string
    {
        $customerCode =
            $this->config['customer_code'] ?? null;

        if (blank($customerCode)) {
            throw new RuntimeException(
                'J&T Cargo CustomerCode belum dikonfigurasi.'
            );
        }

        return (string) $customerCode;
    }

    /**
     * Generate encrypted customer password (pwd).
     *
     * J&T Cargo:
     * pwd = strtoupper(MD5(plainPassword + "jadada236t2"))
     */
    public function generatePasswordCipher(): string
    {
        $customerPassword =
            $this->config['customer_password'] ?? null;

        if (blank($customerPassword)) {
            throw new RuntimeException(
                'J&T Cargo Customer Password belum dikonfigurasi.'
            );
        }

        return strtoupper(
            md5(
                $customerPassword . 'jadada236t2'
            )
        );
    }
}