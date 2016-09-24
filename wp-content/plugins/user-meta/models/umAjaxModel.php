<?php
namespace UserMeta;

class umAjaxModel
{

    function postInsertUser()
    {
        global $userMeta;
        $userMeta->verifyNonce();
        
        $umUserInsert = new UserInsert();
        
        return $umUserInsert->postInsertUserProcess();
    }

    function ajaxValidateUniqueField()
    {
        global $userMeta;
        $userMeta->verifyNonce(false);
        
        $status = false;
        if (! isset($_REQUEST['fieldId']) or ! $_REQUEST['fieldValue'])
            return;
        
        $id = ltrim($_REQUEST['fieldId'], 'um_field_');
        $fields = $userMeta->getData('fields');
        
        if (isset($fields[$id])) {
            $fieldData = $userMeta->getFieldData($id, $fields[$id]);
            $status = $userMeta->isUserFieldAvailable($fieldData['field_name'], $_REQUEST['fieldValue']);
            
            if (! $status) {
                $msg = sprintf(__('%s already taken', $userMeta->name), $_REQUEST['fieldValue']);
                if (isset($_REQUEST['customCheck'])) {
                    echo "error";
                    die();
                }
            }
            
            $response[] = $_REQUEST['fieldId'];
            $response[] = isset($status) ? $status : true;
            $response[] = isset($msg) ? $msg : null;
            
            echo json_encode($response);
        }
        
        die();
    }

    function ajaxFileUploader()
    {
        global $userMeta;
        $userMeta->verifyNonce();
        
        // list of valid extensions, ex. array("jpeg", "xml", "bmp")
        $allowedExtensions = array(
            'jpg',
            'jpeg',
            'png',
            'gif'
        );
        // max file size in bytes
        $sizeLimit = 1 * 1024 * 1024;
        $replaceOldFile = FALSE;
        
        $allowedExtensions = apply_filters('pf_file_upload_allowed_extensions', $allowedExtensions);
        $sizeLimit = apply_filters('pf_file_upload_size_limit', $sizeLimit);
        $replaceOldFile = apply_filters('pf_file_upload_is_overwrite', $replaceOldFile);
        
        $uploader = new FileUploader($allowedExtensions, $sizeLimit);
        $result = $uploader->handleUpload($replaceOldFile);
        // to pass data through iframe you will need to encode all html tags
        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        die();
    }

    function ajaxShowUploadedFile()
    {
        global $userMeta;
        $userMeta->verifyNonce();
        
        if (isset($_REQUEST['showimage'])) {
            if (isset($_REQUEST['imageurl']))
                echo "<img src='{$_REQUEST['imageurl']}' />";
            die();
        }
        
        $file = new File();
        $file->ajaxUpload();
        die();
        
        /**
         * Commented since 1.1.7beta1
         *
         * // Showing Image
         * $fieldID = trim( str_replace( 'um_field_', '', @$_REQUEST['field_id'] ) );
         * $fields = $userMeta->getData( 'fields' );
         * $field = @$fields[@$fieldID];
         * if ( @$field['field_type'] == 'user_avatar' ) {
         * if ( ! empty( $field['image_size'] ) ) {
         * $field['image_width'] = $field['image_size'];
         * $field['image_height'] = $field['image_size'];
         * } else {
         * $field['image_width'] = 96;
         * $field['image_height'] = 96;
         * }
         * }
         *
         * if ( ! empty( $field ) ) {
         * echo $userMeta->renderPro( 'showFile', array(
         * 'filepath' => @$_REQUEST['filepath'],
         * 'field_name' => @$_REQUEST['field_name'],
         * 'width' => @$field['image_width'],
         * 'height' => @$field['image_height'],
         * 'crop' => ! empty( $field['crop_image'] ) ? true : false,
         * //'readonly' => @$fieldReadOnly, // implementation of read-only is not needed.
         * ) );
         * }
         */
        
        die();
    }

    function ajaxWithdrawLicense()
    {
        global $userMeta;
        $userMeta->verifyNonce();
        
        $status = $userMeta->withdrawLicense();
        if (is_wp_error($status))
            echo $userMeta->showError($status);
        elseif ($status === true) {
            echo $userMeta->showMessage(__('License has been withdrawn', $userMeta->name));
            echo $userMeta->jsRedirect($userMeta->adminPageUrl('settings', false));
        } else
            echo $userMeta->showError(__('Something went wrong!', $userMeta->name));
        
        die();
    }

    function ajaxSaveAdvancedSettings()
    {
        global $userMeta;
        $userMeta->checkAdminReferer(__FUNCTION__);
        
        if (! isset($_REQUEST))
            $userMeta->showError(__('Error occurred while updating', $userMeta->name));
        
        $data = $userMeta->arrayRemoveEmptyValue($_REQUEST);
        $data = $userMeta->removeNonArray($data);
        
        $userMeta->updateData('advanced', stripslashes_deep($data));
        echo $userMeta->showMessage(__('Successfully saved.', $userMeta->name));
        
        die();
    }

    function ajaxTestMethod()
    {
        global $userMeta;
        echo 'Working...';
        $userMeta->dump($_REQUEST);
        die();
    }
}