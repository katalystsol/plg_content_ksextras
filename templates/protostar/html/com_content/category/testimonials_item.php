<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content Layout override
 *
 * @copyright   Copyright (C) 2014 Katalyst Solutions, LLC. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;?>
<?php
// Create a shortcut for params.
$params = $this->item->params;
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
$canEdit = $this->item->params->get('access-edit');
JHtml::_('behavior.framework');

$images					= json_decode($this->item->images);
$image					= $images->image_intro ? $images->image_intro : '';
$image_alt				= $images->image_intro_alt ? $images->image_intro_alt : 'photo';
$kstestimonial			= json_decode($this->item->attribs);
$testimonial_by			= $kstestimonial->testimonial_by;
$author_job_title		= $kstestimonial->author_job_title;
$author_affiliation		= $kstestimonial->author_affiliation;
$rating					= !empty($kstestimonial->rating) ? $kstestimonial->rating : 5;
$author_address			= $kstestimonial->author_address;
$author_locality		= $kstestimonial->author_locality;
$author_region			= $kstestimonial->author_region;
$author_postal_code		= $kstestimonial->author_postal_code;
$show_address			= ($author_region) ? true : false;

?>
<?php if ($this->item->state == 0) : ?>
	<span class="label label-warning"><?php echo JText::_('JUNPUBLISHED'); ?></span>
<?php endif; ?>

<?php echo JLayoutHelper::render('joomla.content.blog_style_default_item_title', $this->item); ?>

<?php echo JLayoutHelper::render('joomla.content.icons', array('params' => $params, 'item' => $this->item, 'print' => false)); ?>

<?php // Todo Not that elegant would be nice to group the params ?>
<?php $useDefList = ($params->get('show_modify_date') || $params->get('show_publish_date') || $params->get('show_create_date')
	|| $params->get('show_hits') || $params->get('show_category') || $params->get('show_parent_category') || $params->get('show_author') ); ?>

<?php if ($useDefList) : ?>
	<?php echo JLayoutHelper::render('joomla.content.info_block.block', array('item' => $this->item, 'params' => $params, 'position' => 'above')); ?>
<?php endif; ?>

<?php //echo JLayoutHelper::render('joomla.content.intro_image', $this->item); ?>

<div itemtype="http://schema.org/Review" itemscope="">
<div class="schema-review-description position-off" itemprop="description">Review of <?php echo $this->organization; ?></div>
<div class="schema-item-reviewed" itemtype="http://schema.org/<?php echo $this->organization_type; ?>" itemscope="" itemprop="itemReviewed">
<span class="position-off" itemprop="name"><?php echo $this->organization; ?></span>
</div>
<div class="testimonial-rating position-off" itemprop="reviewRating"><span itemprop="ratingValue"><?php echo $rating; ?></span>/5</div>

<?php if ($image) : ?>
<div class="photo pull-left"><img src="<?php echo $image; ?>" alt="<?php echo $image_alt; ?>" title="<?php echo $testimonial_by; ?>" class="testimonial-photo" /></div>
<?php endif; ?>

<div class="span10">
<div class="testimonial" itemprop="reviewBody">

<?php if (!$params->get('show_intro')) : ?>
	<?php echo $this->item->event->afterDisplayTitle; ?>
<?php endif; ?>
<?php echo $this->item->event->beforeDisplayContent; ?>

<div class="testimonial-body">
&ldquo;<?php echo strip_tags($this->item->introtext, '<strong><em><b><i><a><br>'); ?>&rdquo;
</div>

<?php if ($useDefList) : ?>
	<?php echo JLayoutHelper::render('joomla.content.info_block.block', array('item' => $this->item, 'params' => $params, 'position' => 'below')); ?>
<?php  endif; ?>

<?php if ($params->get('show_readmore') && $this->item->readmore) :
	if ($params->get('access-view')) :
		$link = JRoute::_(ContentHelperRoute::getArticleRoute($this->item->slug, $this->item->catid));
	else :
		$menu = JFactory::getApplication()->getMenu();
		$active = $menu->getActive();
		$itemId = $active->id;
		$link1 = JRoute::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
		$returnURL = JRoute::_(ContentHelperRoute::getArticleRoute($this->item->slug, $this->item->catid));
		$link = new JUri($link1);
		$link->setVar('return', base64_encode($returnURL));
	endif; ?>

	<p class="readmore"><a class="btn" href="<?php echo $link; ?>"> <span class="icon-chevron-right"></span>

	<?php if (!$params->get('access-view')) :
		echo JText::_('COM_CONTENT_REGISTER_TO_READ_MORE');
	elseif ($readmore = $this->item->alternative_readmore) :
		echo $readmore;
		if ($params->get('show_readmore_title', 0) != 0) :
		echo JHtml::_('string.truncate', ($this->item->title), $params->get('readmore_limit'));
		endif;
	elseif ($params->get('show_readmore_title', 0) == 0) :
		echo JText::sprintf('COM_CONTENT_READ_MORE_TITLE');
	else :
		echo JText::_('COM_CONTENT_READ_MORE');
		echo JHtml::_('string.truncate', ($this->item->title), $params->get('readmore_limit'));
	endif; ?>

	</a></p>

<?php endif; ?>
</div>
<?php if ($testimonial_by) : ?>
<div class="testimonial-author" itemtype="http://schema.org/Person" itemscope="" itemprop="author">
&ndash; <span class="testimonial-author-name" itemprop="name"><?php echo $testimonial_by; ?></span><?php if ($author_job_title) : ?>, <?php echo $author_job_title; ?><?php endif; ?>
<br>
<?php echo $author_affiliation; ?>
<?php if ($show_address) : ?>
,
<span class="testimonial-author-address" itemtype="http://schema.org/PostalAddress" itemscope="" itemprop="address">
<?php if ($author_locality) : ?>
<span itemprop="addressLocality"><?php echo $author_locality; ?></span>
,
<?php endif; ?>
<span itemprop="addressRegion"><?php echo $author_region; ?></span>
</span>
<?php endif; ?>
</div>
</div>
</div>
<?php endif; ?>
<?php echo $this->item->event->afterDisplayContent; ?>
