<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Domain;

enum MagentoArea: string
{
    public const AREA_ADMINHTML = 'adminhtml';
    public const AREA_FRONTEND = 'frontend';
    public const AREA_WEBAPI_REST = 'webapi_rest';
    public const AREA_WEBAPI_SOAP = 'webapi_soap';
    public const AREA_GRAPHQL = 'graphql';

    case ADMINHTML = self::AREA_ADMINHTML;
    case FRONTEND = self::AREA_FRONTEND;
    case WEBAPI_REST = self::AREA_WEBAPI_REST;
    case WEBAPI_SOAP = self::AREA_WEBAPI_SOAP;
    case GRAPHQL = self::AREA_GRAPHQL;
}
