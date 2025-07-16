<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\Exceptions\ApiException;
use App\Classes\eHealth\Request;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class RuleEngineRulesApi
{
    protected const string ENDPOINT_RULE_ENGINE_RULES = '/api/rule_engine_rules';

    /**
     * Get a catalog of all active rule engine rules.
     *
     * @param  array  $params
     * @return array
     * @throws ApiException
     */
    public static function getRuleEngineRuleList(array $params = []): array
    {
        return new Request(HttpRequest::METHOD_GET, self::ENDPOINT_RULE_ENGINE_RULES, $params)->sendRequest();
    }

    /**
     * Get rule engine rule details filtered by ID with active rules.
     *
     * @param  string  $id
     * @return array
     * @throws ApiException
     */
    public static function getRuleEngineRuleDetails(string $id): array
    {
        return new Request(HttpRequest::METHOD_GET, self::ENDPOINT_RULE_ENGINE_RULES . "/$id", [])->sendRequest();
    }
}
