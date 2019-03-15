<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\DataHubBundle\GraphQL\Query\Value;

use GraphQL\Type\Definition\ResolveInfo;
use Pimcore\Bundle\DataHubBundle\GraphQL\Service;
use Pimcore\Bundle\DataHubBundle\PimcoreDataHubBundle;
use Pimcore\Bundle\DataHubBundle\WorkspaceHelper;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Helper\Pimcore;

class DefaultValue extends AbstractValue
{
    /**
     * @param ElementInterface|Concrete $element
     *
     * {@inheritdoc}
     */
    public function getLabeledValue($element, ResolveInfo $resolveInfo = null)
    {
        if ($element) {
            if ($this->dataType == 'system') {
                $getter = 'get' . ucfirst($this->attribute);

                return $element->$getter();
            }

            $class = $element->getClass();
            $fieldHelper = \Pimcore::getContainer()->get('pimcore.datahub.graphql.fieldhelper.object');
            $fieldDefinition = $fieldHelper->getFieldDefinitionFromKey($class, $this->attribute);
            $service = \Pimcore::getContainer()->get(Service::class);
            $valueParams = [
                'id' => $element->getId(),
            ];

            $resolveFn = $service->buildDataQueryResolver($fieldDefinition, $class);
            $args = null;
            $context = null;

            $value = $resolveFn($valueParams, $args, $this->context, $resolveInfo);
            if ($value) {
                if ($value instanceof ElementInterface) {
                    $configuration = $this->context['configuration'];
                    if (!WorkspaceHelper::isAllowed($value, $configuration, 'read')) {
                        if (PimcoreDataHubBundle::getNotAllowedPolicy() == PimcoreDataHubBundle::NOT_ALLOWED_POLICY_EXCEPTION) {
                            throw new \Exception('not allowed to view ' . $value->getFullPath());
                        } else {
                            return null;
                        }
                    }
                }

                $result = new \stdClass();
                $result->value = $value;

                return $result;
            }
        }
    }
}
