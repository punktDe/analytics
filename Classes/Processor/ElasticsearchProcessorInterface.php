<?php
/*
 *  (c) 2019 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

namespace PunktDe\Analytics\Processor;


interface ElasticsearchProcessorInterface
{
    /**
     * @param array $record
     * @param string $indexName
     * @return array|null
     */
    public function convertRecordToDocument(array $record, string $indexName): ?array;
}
