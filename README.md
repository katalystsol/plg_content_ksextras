plg_content_ksextras
====================

Add additional fields for articles in Joomla com_content

This plugin is to allow a Joomla site to add additional fields for com_content without modifying the core system or modifying the #__content table in the database.

It is designed for Joomla 3.3 but will likely work with earlier versions of Joomla 3.

This plugin is still under development and testing...

####### To add a field(s)
1. In ksextras.php, add the field to array in function setKSField()
2. In extras/testimonial.xml add the field(s)
3. In language/en-GB/en-GB.plg_content_ksextras.ini add the language strings


Sample layout override output for a testimonial:
````
<pre>
<div itemtype="http://schema.org/Review" itemscope="">
<div class="schema-review-description" itemprop="description">Review of {organization}</div>
<div class="schema-item-reviewed" itemtype="http://schema.org/{organization_type}" itemscope="" itemprop="itemReviewed">
<span itemprop="name">{organization}</span>
</div>
<div class="testimonial-rating" itemprop="reviewRating"><span itemprop="ratingValue">{rating}</span>/5</div>
<div class="testimonial" itemprop="reviewBody">{text}</div>
<div class="testimonial-author" itemtype="http://schema.org/Person" itemscope="" itemprop="author">
-
<span class="testimonial-author-name" itemprop="name">{testimonial_by}</span>
,
<span class="testimonial-author-address" itemtype="http://schema.org/PostalAddress" itemscope="" itemprop="address">
<span itemprop="addressLocality">{author_locality}</span>
,
<span itemprop="addressRegion">{author_region}</span>
</span>
</div>
</div>
</pre>
````