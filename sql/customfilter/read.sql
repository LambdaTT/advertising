SELECT 
    ftr.*,
    cst.ds_fieldlabel,
    CASE
      WHEN cst.do_fieldtype = 'T' THEN 'text'
      WHEN cst.do_fieldtype = 'N' THEN 'number'
      ELSE 'text'
    END as typeText
  FROM `ADV_TARGETCUSTOMFILTER` ftr
  JOIN `CST_CUSTOMFIELD` cst ON cst.id_cst_customfield = ftr.id_cst_customfield