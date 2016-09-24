<?php
namespace UserMeta;

class umSupportHtml
{

    function boxHowToUse()
    {
        global $userMeta;
        
        $html = null;
        $html .= sprintf('<p><strong>Step 1.</strong> Create a form and populate it with fields by %s page.</p>', $userMeta->adminPageUrl('forms'));
        $html .= sprintf('<p><strong>Step 2.</strong> Write shortcode to your page or post. Shortcode (e.g.): %s</p>', '[user-meta-profile form="Form_Name"]');
        $html .= "<div><center><a class=\"button-primary\" href=\"" . $userMeta->website . "\">" . __('Visit Plugin Site', $userMeta->name) . "</a></center></div>";
        return $html;
    }

    function boxGetPro()
    {
        global $userMeta;
        
        $html = null;
        $html .= "<div style='padding-left: 10px'>";
        $html .= "<p>Get <strong>User Meta Pro</strong> for : </p>";
        $html .= "<li>Frontend Login shortcode and widget.</li>";
        $html .= "<li>Allow user to login with their Email or Username.</li>";
        $html .= "<li>Add extra fields to backend profile.</li>";
        $html .= "<li>Role based user redirection on login, logout and registratioin.</li>";
        $html .= "<li>User activatation/deactivation, Admin approval on new user registration.</li>";
        $html .= "<li>Customize email notification with including extra field's data.</li>";
        $html .= "<li>Frontend password reset.</li>";
        $html .= "<p></p>";
        $html .= "<li>Advanced fields for creating profile/registration form.</li>";
        $html .= "<li>Fight against spam by Captcha.</li>";
        $html .= "<li>Split your form into multiple page by using Page Heading</li>";
        $html .= "<li>Group fields using Section Heading.</li>";
        $html .= "<li>Allow user to upload their file by File Upload.</li>";
        $html .= "<li>Country Dropdown for country selection.</li>";
        $html .= "<li>Use Custom Field to build custom input field</li>";
        $html .= "<li>Use free add-ons</li>";
        $html .= "<br />";
        $html .= "<center><a class='button-primary' href='http://user-meta.com'>Get User Meta Pro</a></center>";
        $html .= "</div>";
        
        return $html;
    }

    function boxLiveDemo()
    {
        $html = null;
        $html .= "<div style='padding-left: 10px'>";
        $html .= "<p>See live demo of <strong>User Meta Pro</strong></p>";
        $html .= "<center><a class='button-primary' href='http://demo.user-meta.com/'>Live Demo</a></center>";
        $html .= "</div>";
        return $html;
    }

    function boxShortcodesDocs()
    {
        global $userMeta;
        
        $html = null;
        $html .= "<div style='padding-left: 10px'>";
        $html .= '<p><div><strong>Profile shortcode</div></strong>[user-meta-profile form="Form_Name"]</p>';
        $html .= '<p><div><strong>Registration shortcode</strong></div>[user-meta-registration form="Form_Name"]</p>';
        $html .= '<p><div><strong>Profile / Registration</strong></div><div>[user-meta type=profile-registration form="Form_Name"]</div><div><em>(To show user profile if user logged in, or showing registration form, if user not logged in.)</em></div></p>';
        $html .= '<p><div><strong>Public profile</strong></div><div>[user-meta type=public form="Form_Name"]</div><div><em>(To show public profile if user_id parameter provided as GET request.)</em></div></p>';
        if ($userMeta->isPro()) {
            $html .= '<p><div><strong>Login shortcode</strong></div>[user-meta-login] OR [user-meta-login form="Form_Name"]</p>';
            $html .= '<p><div><strong>Field shortcode</strong></div>[user-meta-field id=Field_ID]</p>';
            $html .= '<p><div><strong>Field content shortcode</strong></div>[user-meta-field-value id=Field_ID] OR [user-meta-field-value key=meta_key]</p>';
        }
        $html .= "<p></p>";
        $html .= "<center><a class='button-primary' href='http://user-meta.com/documentation/shortcodes/'>" . __('Read More', $userMeta->name) . "</a></center>";
        $html .= "</div>";
        return $html;
    }

    function boxLinks()
    {
        global $userMeta;
        
        $html = "<div style='padding-left: 10px'>";
        $html .= '<li><a href="http://user-meta.com/documentation/">' . __('Documentation', $userMeta->name) . '</a></li>';
        $html .= '<li><a href="http://user-meta.com/videos/">' . __('Video Tutorials', $userMeta->name) . '</a></li>';
        if ($userMeta->isPro()) {
            $html .= '<li><a href="http://user-meta.com/forums/">' . __('Forums', $userMeta->name) . '</a></li>';
        }
        $html .= '<li><a href="http://user-meta.com/add-ons/">' . __('Add Ons', $userMeta->name) . '</a></li>';
        $html .= '<li><a href="http://demo.user-meta.com/">' . __('Live Demo', $userMeta->name) . '</a></li>';
        $html .= "</div>";
        return $html;
    }

    function boxTips()
    {
        global $userMeta;
        
        $html = "<div style='padding-left: 10px'>";
        $html .= "</div>";
        return $html;
    }

    function getProLink($label = null)
    {
        global $userMeta;
        
        $label = $label ? $label : $userMeta->website;
        return "<a href=\"{$userMeta->website}\">$label</a>";
    }

    function proDemoImage($img = null)
    {
        global $userMeta;
        
        if ($userMeta->isPro)
            $html = adminNotice("Please validate your license to use this feature.");
        else
            $html = adminNotice("This feature is only supported in Pro version. Get <a href=\"{$userMeta->website}\">User Meta Pro</a>");
        
        if ($img)
            $html .= "<img src=\"https://s3.amazonaws.com/user-meta/public/plugin/images/{$img}?ver={$userMeta->version}\" width=\"100%\" onclick=\"umGetProMessage(this)\" />";
        
        return $html;
    }

    function showInfo($data, $title = '', $icon = true)
    {
        $iconHtml = "<span style=\"display: inline-block;\" class=\"ui-icon ui-icon-info\"></span>";
        
        if ($icon)
            $title .= $iconHtml;
        
        $title = $title ? $title : $iconHtml;
        
        return "<p data-ot='$data' class='my-element' >$title</p>";
    }

    function buildPanel($title, $body)
    {
        ?>
<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">
                    <?php echo $title; ?> <i class="fa fa-caret-down"></i>
		</h3>
	</div>
	<div class="panel-collapse collapse">
		<div class="panel-body"><?php echo $body; ?>
                </div>
	</div>
</div>
<?php
    }

    function buildTabs($name = null, $tabs = array())
    {
        $li = null;
        $tabContent = null;
        $active = 'active';
        
        foreach ($tabs as $title => $content) {
            $id = str_replace(' ', '_', strtolower($title));
            if (! empty($name))
                $id = "{$name}_{$id}";
            
            $li .= "<li class=\"nav $active\"><a href=\"#{$id}\" data-toggle=\"tab\">$title</a></li>";
            
            if ($active)
                $active = 'in active';
            $tabContent .= "<div class=\"tab-pane fade $active\" id=\"$id\">$content</div>";
            
            if ($active)
                $active = null;
        }
        
        $html = '<ul class="nav nav-tabs">' . $li . '</ul>';
        $html .= '<div class="tab-content">' . $tabContent . '</div>';
        
        return $html;
    }
}