SELECT 
    adv.*,
    DATE_FORMAT(adv.dt_start, '%d/%m/%Y %T') as dtStart,
    DATE_FORMAT(adv.dt_end, '%d/%m/%Y %T') as dtEnd,
    CASE
      WHEN adv.do_interval = 'D' THEN 'Diariamente'
      WHEN adv.do_interval = 'W' THEN 'Semanalmente'
      WHEN adv.do_interval = 'M' THEN 'Mensalmente'
      WHEN adv.do_interval = 'Y' THEN 'Anualmente' 
    END intervalText,
    -- Audit
    DATE_FORMAT(adv.dt_created, '%d/%m/%Y %T') as dtCreated, 
    DATE_FORMAT(adv.dt_updated, '%d/%m/%Y %T') as dtUpdated, 
    CONCAT(usrc.ds_first_name, ' ', usrc.ds_last_name) as userCreated,
    CONCAT(usru.ds_first_name, ' ', usru.ds_last_name) as userUpdated
  FROM `ADV_ADVERTISEMENT` adv
  LEFT JOIN `IAM_USER` usrc ON usrc.id_iam_user = adv.id_iam_user_created
  LEFT JOIN `IAM_USER` usru ON usru.id_iam_user = adv.id_iam_user_updated