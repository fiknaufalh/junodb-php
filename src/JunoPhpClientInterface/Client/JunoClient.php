<?php

namespace JunoPhpClient\Client;

use JunoPhpClient\Exception\JunoException;
use JunoPhpClient\Client\IO\JunoRequest;
use JunoPhpClient\Client\IO\JunoResponse;
use JunoPhpClient\Client\IO\RecordContext;

interface JunoClient
{
    /**
     * Insert a record into Juno DB with default TTL
     * @param string $key Key of the record to be Inserted
     * @param string $value Record Value
     * @return JunoResponse Juno Response object which contains the status of the operation
     * @throws JunoException Throws Exception if any exception while processing the request
     */
    public function create(string $key, string $value): JunoResponse;

    /**
     * Insert a record into Juno DB with user supplied TTL
     * @param string $key Key of the record to be Inserted
     * @param string $value Record Value
     * @param int $timeToLiveSec Time to Live for the record
     * @return JunoResponse Juno Response object which contains the status of the operation
     * @throws JunoException Throws Exception if any exception while processing the request
     */
    public function createWithTTL(string $key, string $value, int $timeToLiveSec): JunoResponse;

    /**
     * Get a record from Juno DB
     * @param string $key Key of the record to be retrieved
     * @return JunoResponse Juno Response object which contains the status of the operation, 
     *                      version of the record and value of the record.
     * @throws JunoException Throws Exception if any issue while processing the request
     */
    public function get(string $key): JunoResponse;

    /**
     * Get a record from Juno DB and Extend the TTL
     * @param string $key Key of the record to be retrieved
     * @param int $timeToLiveSec Time to Live for the record
     * @return JunoResponse Juno Response object which contains the status of the operation, 
     *                      version of the record and value of the record.
     * @throws JunoException Throws Exception if any exception while processing the request
     */
    public function getWithTTL(string $key, int $timeToLiveSec): JunoResponse;

    /**
     * Update a record in Juno DB
     * @param string $key Key of the record to be Updated
     * @param string $value Record Value
     * @return JunoResponse Juno Response object which contains the status of the operation
     * @throws JunoException Throws Exception if any issue while processing the request
     */
    public function update(string $key, string $value): JunoResponse;

    /**
     * Update a record in Juno DB and Extend its TTL
     * @param string $key Key of the record to be Updated
     * @param string $value Record Value
     * @param int $timeToLiveSec Time to Live for the record
     * @return JunoResponse Juno Response object which contains the status of the operation.
     * @throws JunoException Throws Exception if any exception while processing the request
     */
    public function updateWithTTL(string $key, string $value, int $timeToLiveSec): JunoResponse;

    /**
     * Update the record if present in Juno DB else create that record with the default TTL in the configuration
     * @param string $key Key of the record to be Upserted
     * @param string $value Record Value
     * @return JunoResponse Juno Response object which contains the status of the operation
     * @throws JunoException Throws Exception if any exception while processing the request
     */
    public function set(string $key, string $value): JunoResponse;

    /**
     * Update the record if present in Juno DB and extend its TTL else create that record with the supplied TTL.
     * @param string $key Key of the record to be Upserted
     * @param string $value Record Value
     * @param int $timeToLiveSec Time to Live for the record
     * @return JunoResponse Juno Response object which contains the status of the operation
     * @throws JunoException Throws Exception if any exception while processing the request
     */
    public function setWithTTL(string $key, string $value, int $timeToLiveSec): JunoResponse;

    /**
     * Delete the record from Juno DB
     * @param string $key Record Key to be deleted
     * @return JunoResponse Juno Response object which contains the status of the operation
     * @throws JunoException Throws Exception if any exception while processing the request
     */
    public function delete(string $key): JunoResponse;

    /**
     * Compare the version of the record in Juno DB and update it only if the supplied version
     * is greater than or equal to the existing version in Juno DB
     * @param RecordContext $jcx Record context from a previous Get operation
     * @param string $value Record Value
     * @param int $timeToLiveSec Time to Live for the record. If set to 0 then the TTL is not extended.
     * @return JunoResponse Juno Response object which contains the status of the operation
     * @throws JunoException Throws Exception if any exception while processing the request
     */
    public function compareAndSet(RecordContext $jcx, string $value, int $timeToLiveSec): JunoResponse;

    /**
     * Perform batch operation on list of requests
     * @param JunoRequest[] $request List of requests with necessary data for that operation
     * @return JunoResponse[] List of responses for the requests
     * @throws JunoException Throws Exception if any issue while processing the requests
     */
    public function doBatch(array $request): array;

    /**
     * Return the properties of the current bean in a MAP
     * The map consists of property name and its value. Property name can be found
     * in com.paypal.juno.conf.JunoProperties
     */
    public function getProperties(): array;
}