SELECT 
    ftr.*,
    cst.ds_fieldlabel,
    CASE
      WHEN cst.do_fieldtype = 'T' THEN 'text'
      WHEN cst.do_fieldtype = 'N' THEN 'number'
      ELSE 'text'
    END as typeText
  FROM `ADV_TARGETCUSTOMFILTER` ftr
  JOIN `STT_SETTINGS_CUSTOMFIELD` cst ON cst.id_stt_settings_customfield = ftr.id_stt_settings_customfield
