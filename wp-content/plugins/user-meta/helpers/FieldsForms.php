<?php
namespace UserMeta;

/**
 * Get meta_key(s) of all file fields.
 */
function getFileMetaKeys()
{
    $fields = (new FormBase())->getAllFields();
    $metaKeys = [
        'user_avatar'
    ];
    foreach ($fields as $field) {
        if ($field['field_type'] == 'file') {
            $metaKeys[] = $field['meta_key'];
        }
    }
    
    return array_unique($metaKeys);
}