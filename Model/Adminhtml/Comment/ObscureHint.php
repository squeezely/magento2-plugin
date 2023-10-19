<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Adminhtml\Comment;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Config\Model\Config\CommentInterface;

class ObscureHint extends AbstractBlock implements CommentInterface
{

    public function getCommentText($elementValue)
    {
        if (empty($elementValue)) {
            return '';
        }
        return __('The current value starts with <strong>%1</strong>', substr($elementValue, -4));
    }
}
