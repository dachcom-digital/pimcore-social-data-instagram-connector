<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace SocialData\Connector\Instagram\Builder\Type;

use SocialDataBundle\Dto\TransformData;

class InstagramLoginBuilder extends AbstractBuilder
{
    public function transform(TransformData $data): void
    {
        $socialPost = $data->getSocialPostEntity();

        $this->transformPost($data);

        $data->setTransformedElement($socialPost);
    }
}
