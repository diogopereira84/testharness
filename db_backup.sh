#!/bin/bash

CURRENT_DATE=$(date +"%Y%m%d%H%M%S")

# Moving dumps to the stages
DB_FILE="dump_${CURRENT_DATE}.sql.gz"
BACKUP_FOLDER="var/db_backups/"

EXCLUDED_TABLES=(
    "admin_analytics_usage_version_log"
    "admin_passwords"
    "admin_system_messages"
    "admin_user"
    "admin_user_expiration"
    "admin_user_session"
    "adminnotification_inbox"
    "authorization_role"
    "authorization_rule"
    "cache"
    "cache_tag"
    "captcha_log"
    "customer_address_entity"
    "customer_address_entity_datetime"
    "customer_address_entity_decimal"
    "customer_address_entity_int"
    "customer_address_entity_text"
    "customer_address_entity_varchar"
    "customer_entity"
    "customer_entity_datetime"
    "customer_entity_decimal"
    "customer_entity_int"
    "customer_entity_text"
    "customer_entity_varchar"
    "customer_grid_flat"
    "customer_dummy_cl"
    "customer_log"
    "cron_schedule"
    "fastly_modly_manifests"
    "fastly_statistics"
    "import_history"
    "importexport_importdata"
    "magento_giftcard_amount"
    "magento_giftcardaccount"
    "magento_giftcardaccount_history"
    "magento_giftcardaccount_pool"
    "magento_giftregistry_data"
    "magento_giftregistry_entity"
    "magento_giftregistry_item"
    "magento_giftregistry_item_option"
    "magento_giftregistry_label"
    "magento_giftregistry_person"
    "magento_giftregistry_type"
    "magento_giftregistry_type_info"
    "magento_giftwrapping"
    "magento_giftwrapping_store_attributes"
    "magento_giftwrapping_website"
    "oauth_consumer"
    "oauth_nonce"
    "oauth_token"
    "oauth_token_request_log"
    "queue"
    "queue_lock"
    "queue_message"
    "queue_message_status"
    "queue_poison_pill"
    "sales_bestsellers_aggregated_daily"
    "sales_bestsellers_aggregated_monthly"
    "sales_bestsellers_aggregated_yearly"
    "sales_creditmemo"
    "sales_creditmemo_comment"
    "sales_creditmemo_grid"
    "sales_creditmemo_item"
    "sales_invoice"
    "sales_invoice_comment"
    "sales_invoice_grid"
    "sales_invoice_item"
    "sales_invoiced_aggregated"
    "sales_invoiced_aggregated_order"
    "sales_order"
    "sales_order_address"
    "sales_order_aggregated_created"
    "sales_order_aggregated_updated"
    "sales_order_grid"
    "sales_order_item"
    "sales_order_payment"
    "sales_order_status"
    "sales_order_status_history"
    "sales_order_status_label"
    "sales_order_status_state"
    "sales_order_tax"
    "sales_order_tax_item"
    "sales_payment_transaction"
    "sales_refunded_aggregated"
    "sales_refunded_aggregated_order"
    "sales_shipment"
    "sales_shipment_comment"
    "sales_shipment_grid"
    "sales_shipment_item"
    "sales_shipment_track"
    "sales_shipping_aggregated"
    "sales_shipping_aggregated_order"
    "session"
    "support_backup"
    "support_backup_item"
    "support_report"
    "variable"
    "variable_value"
    "magento_customercustomattributes_sales_flat_quote_address"
    "magento_customercustomattributes_sales_flat_quote"
    "magento_customercustomattributes_sales_flat_order_address"
    "magento_logging_event_changes"
    "magento_bulk"
    "magento_operation"
    "magento_acknowledged_bulk"
    "tax_order_aggregated_created"
    "tax_order_aggregated_updated"
    "queue"
    "queue_lock"
    "queue_message"
    "queue_message_status"
    "queue_poison_pill"
    "quote"
    "quote_address"
    "quote_address_item"
    "quote_id_mask"
    "quote_integration"
    "quote_integration_item"
    "quote_integration_note"
    "quote_item"
    "quote_item_option"
    "quote_notification_log"
    "quote_payment"
    "quote_preview"
    "quote_shipping_rate"
    "wishlist"
    "wishlist_item"
    "wishlist_item_option"
    "wishlist"
    "core_config_data"
    "setup_module"
    "temp_order_compression"
    "temp_quote_compression"
    "targetrule_product_rule_cl"
    "targetrule_rule_product_cl"
    "shared_catalog_sync_queue_configuration"
    "catalog_sync_queue_cleanup_process"
    "catalog_sync_queue"
    "catalog_sync_queue_cleanup_process"
    "catalog_sync_queue_process"
    "search_query"
    "search_synonyms"
    "sendfriend_log"
    "scopes_customergroup_data_exporter_cl"
    "scopes_website_data_exporter_cl"
    "salesrule_rule_cl"
    "release_notification_viewer_log"
    "report_compared_product_index"
    "report_event"
    "report_event_types"
    "report_viewed_product_aggregated_daily"
    "report_viewed_product_aggregated_monthly"
    "report_viewed_product_aggregated_yearly"
    "report_viewed_product_index"
    "reporting_counts"
    "reporting_module_status"
    "reporting_orders"
    "reporting_system_updates"
    "purchase_order_log"
    "magento_rma"
    "magento_rma_grid"
    "magento_rma_item_eav_attribute"
    "magento_rma_item_eav_attribute_website"
    "magento_rma_item_entity"
    "magento_rma_item_entity_datetime"
    "magento_rma_item_entity_decimal"
    "magento_rma_item_entity_int"
    "magento_rma_item_entity_text"
    "magento_rma_item_entity_varchar"
    "magento_rma_item_form_attribute"
    "magento_rma_shipping_label"
    "magento_rma_status_history"
    "inventory_cl"
    "customer_dummy_cl"
    "catalogrule_rule_cl"
    "catalogsearch_fulltext_cl"
    "catalogpermissions_category_cl"
    "catalogpermissions_product_cl"
    "catalog_product_category_cl"
    "catalog_product_attribute_cl"
    "catalog_data_exporter_products_cl"
    "catalog_data_exporter_categories_cl"
    "catalog_data_exporter_category_permissions_cl"
    "catalog_data_exporter_product_attributes_cl"
    "catalog_data_exporter_product_overrides_cl"
    "catalog_data_exporter_product_prices_cl"
    "catalog_data_exporter_product_variants_cl"
    "catalog_category_product_cl"
    "admin_adobe_ims_webapi"
    "amazon_customer"
    "amazon_pending_authorization"
    "amazon_pending_capture"
    "amazon_pending_refund"
    "amazon_quote"
    "amazon_sales_order"
    "adobe_stock_asset"
    "adobe_stock_category"
    "adobe_stock_creator"
    "magento_invitation"
    "magento_invitation_status_history"
    "magento_invitation_track"
    "magento_logging_event"
    "magento_logging_event_changes"
    "magento_login_as_customer_log"
)

# Check for duplications
duplicate_tables=()
for table in "${EXCLUDED_TABLES[@]}"; do
    count=$(grep -c "^$table$" <<< "${EXCLUDED_TABLES[*]}")
    if (( count > 1 )); then
        duplicate_tables+=("$table")
    fi
done

if [ ${#duplicate_tables[@]} -gt 0 ]; then
    echo "Error: Duplicate tables found:"
    for table in "${duplicate_tables[@]}"; do
        echo "$table"
    done
    exit 1
fi

REMOTE_INSTANCE=false
DUMP_FLAG=false
MOVE_FLAG=false
UNZIP_FLAG=false
FILE_SYNC=false
IMPORT_FLAG=false
REMOTE_USER_SOURCE=
REMOTE_PATH=
LOCAL_PATH=
UNZIP_FILE=
LOCAL_FILE=
FILE_TO_EDIT=
SOURCE_PATH=
DESINATION_PATH=
PUSH_FILE=
IMPORT_FILE=

# Help function
show_help() {
    echo "Usage: $(basename "$0") [options]"
    echo "Options:"
    echo "  -r          <remote_instance> Specify the remote instance where need to take backup"
    echo "  -d          Create a database dump in writable dirctory on remote server backup folder is var/db_backups/"
    echo "  -m          <local_path> <file is optional> Move the database dump to local path"
    echo "  -u          <unzip_file> <file is optional> Unzip the dump file"
    echo "  -s          <source><desination><file is optional> Sync the file to remote server or local using scp"
    echo "  -i          <remote_instance><remote_file> Import the database dump remote file will be full path"
    echo "  -c          <source_server:source_path> <destination_server:destination_path> Copy a file from one server to another using scp"
    echo "  -h          Show this help message"
    exit 0
}

# Parse command line options
#while getopts ":dms:e:u:f:r:h" opt; do
while getopts ":r:dm:u:s:i:c:h" opt; do

  case $opt in
    r)
        REMOTE_INSTANCE=true
        REMOTE_USER_SOURCE=$OPTARG
        ;;
    d)
      DUMP_FLAG=true
      ;;
    m)
      MOVE_FLAG=true
      IFS=' ' read -ra ADDR <<< "$OPTARG"
      LOCAL_PATH="${ADDR[0]}"
      FILE_TO_EDIT="${ADDR[1]}"
      ;;
    u)
      UNZIP_FLAG=true
      UNZIP_FILE=${OPTARG:-$FILE_TO_EDIT}
      ;;
    s)
      SYNC_FLAG=true
      IFS=' ' read -ra ADDR <<< "$OPTARG"
      SOURCE_PATH="${ADDR[0]}"
      DESINATION_PATH="${ADDR[1]}"
      PUSH_FILE="${ADDR[2]}"
      ;;
   i)
      IMPORT_FLAG=true
      IFS=' ' read -ra ADDR <<< "$OPTARG"
      IMPORT_PATH="${ADDR[0]}"
      IMPORT_FILE="${ADDR[1]}"
      ;;
  c)
      COPY_FLAG=true
      IFS=':' read -r SOURCE_SERVER SOURCE_PATH <<< "$OPTARG"
      shift
      IFS=':' read -r DESTINATION_SERVER DESTINATION_PATH <<< "$OPTARG"
      ;;
    h)
      show_help
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
    :)
      echo "Option -$OPTARG requires an argument." >&2
      exit 1
      ;;
  esac
done

# If no options are specified, show help message
if [[ $# -eq 0 ]]; then
    show_help
fi

if [ "$DUMP_FLAG" = true ] || [ "$MOVE_FLAG" = true ]; then
    if [ -z "$REMOTE_USER_SOURCE" ]; then
        echo "Error: Remote user source is required for database dump."
        exit 1
    fi
fi
# Create the DB Dums on the remote server
if [ "$DUMP_FLAG" = true ]; then
    read -p "This will create a database dump. Continue? (y/n): " CONFIRM_DUMP
    if [ "$CONFIRM_DUMP" == "y" ] || [ "$CONFIRM_DUMP" == "Y" ]; then
        DATABASE=$(ssh ${REMOTE_USER_SOURCE} 'php -r "\$config = include(\"app/etc/env.php\"); echo \$config[\"db\"][\"connection\"][\"default\"][\"dbname\"];"')
        IGNORED_TABLES_STRING=''
        # Iterate over the array and append each table name prefixed by the database name
        for table in "${EXCLUDED_TABLES[@]}"; do
           IGNORED_TABLES_STRING="${IGNORED_TABLES_STRING}${DATABASE}.${table},"
        done
        # Remove the trailing comma
        IGNORED_TABLES_STRING="--ignore-table={"${IGNORED_TABLES_STRING%,}"}"

        # Dump structure and content, compress on the fly
        #ssh ${REMOTE_USER_SOURCE} "mkdir -p "${REMOTE_FOLDER_SOURCE}" && mysqldump -u"${USER}" -p"${PASSWORD}" -h"${HOST}" --single-transaction" ${IGNORED_TABLES_STRING} ${DATABASE}" | gzip >" ${REMOTE_FOLDER_SOURCE}${DB_FILE}
        ssh ${REMOTE_USER_SOURCE} "mkdir -p ${BACKUP_FOLDER} && mysqldump -u\$(php -r \"echo (include('app/etc/env.php'))['db']['connection']['default']['username'];\") -p\$(php -r \"echo (include('app/etc/env.php'))['db']['connection']['default']['password'];\") -h\$(php -r \"echo (include('app/etc/env.php'))['db']['connection']['default']['host'];\") --single-transaction ${IGNORED_TABLES_STRING} \$(php -r \"echo (include('app/etc/env.php'))['db']['connection']['default']['dbname'];\") | sed -e 's/DEFINER=[^*]*\*/\*/g' | gzip > ${BACKUP_FOLDER}${DB_FILE}"
        if [ $? -ne 0 ]; then
            echo "Error dumping and compressing. Check the logs for details."
            exit 1
        fi
        FILE_TO_EDIT=${BACKUP_FOLDER}${DB_FILE}
        echo "Backup completed successfully. Compressed backup file: ${BACKUP_FOLDER}${DB_FILE}"
    else
        echo "Operation canceled by the user."
        exit 0
    fi
fi

# Move the DB Dumps to the remote server to local path
if [ "$MOVE_FLAG" = true ] && [ -n "$FILE_TO_EDIT" ] && [ -n "$LOCAL_PATH" ]; then
    # Move the specified file if provided, else move the database dump
    if [ -n "$FILE_TO_EDIT" ]; then
        # Copy the specified file to the remote server via scp
        mkdir -p ${LOCAL_PATH}
        scp ${REMOTE_USER_SOURCE}:${FILE_TO_EDIT} $LOCAL_PATH
        if [ $? -ne 0 ]; then
            echo "Error copying the file to the remote server. Check the logs for details."
            exit 1
        fi
        UNZIP_FILE=$LOCAL_PATH${DB_FILE}
        echo "File copied to local path: ${LOCAL_PATH}${DB_FILE}"
    fi

    # Ask if the local file should be deleted after move
    if [ -n "$FILE_TO_EDIT" ]; then
        read -p "Do you want to delete the local file after moving to the remote server? (y/n): " DELETE_LOCAL_FILE
    fi

    if [ "$DELETE_LOCAL_FILE" == "y" ] || [ "$DELETE_LOCAL_FILE" == "Y" ]; then
        if [ -n "$FILE_TO_EDIT" ]; then
            ssh ${REMOTE_USER_SOURCE} rm "$FILE_TO_EDIT"
            echo "Remote file deleted: $FILE_TO_EDIT"
        fi
    fi
fi
#exit 1
# Do Unzip on local path
if [ "$UNZIP_FLAG" = true ]; then
     if [ -z "$UNZIP_FILE" ]; then
            echo "Error: Please provide file to uzip."
            exit 1
        fi
    # Unzip the specified file
    gunzip $UNZIP_FILE
    if [ $? -ne 0 ]; then
        echo "Error unzipping the file."
        exit 1
    fi
    PUSH_FILE="${UNZIP_FILE%.gz}"
    echo "File successfully unzipped: $PUSH_FILE"
fi

# Download file from the remote server if specified
if [ "$SYNC_FLAG" = true ]; then
     if [ -z "$SOURCE_PATH" ]; then
            echo "Error: Source file is required to sync."
            exit 1
     fi
     if [ -z "$DESINATION_PATH" ]; then
            echo "Error: Source file is required to sync."
            exit 1
    fi
     if [ -z "$PUSH_FILE" ]; then
           echo "Error: Source file is required to sync."
           exit 1
     fi
    if [[ $SOURCE_PATH == *".magento.cloud"* ]]; then
        scp ${SOURCE_PATH}:${PUSH_FILE} $DESINATION_PATH
        $IMPORT_FILE=${PUSH_FILE}
    else
        if [[ $DESINATION_PATH == *"production"* ]]; then
             echo "Error: Not allowed to push any file to production."
            exit 1
        fi
        scp $SOURCE_PATH$(basename "$PUSH_FILE") ${DESINATION_PATH}:${BACKUP_FOLDER}$(basename "$PUSH_FILE")
        IMPORT_PATH=$DESINATION_PATH;
        IMPORT_FILE=$(basename "$PUSH_FILE");
    fi
    if [ $? -ne 0 ]; then
        echo "Error downloading the file from the remote server. Check the logs for details."
        exit 1
    fi
    echo "File moved to the remote server: $DESINATION_PATH -> $(basename "$PUSH_FILE")"
fi
#Start importing the database dump
if [ "$IMPORT_FLAG" = true ]; then
    if [ -z "$IMPORT_FILE" ]; then
        echo "Error: Please provide file to import."
        exit 1
    fi
    if [ -z "$IMPORT_PATH" ]; then
        echo "Error: Please provide desination path where need to import."
        exit 1
    fi
     if [[ $IMPORT_PATH == *".magento.cloud"* ]]; then
         if [[ $IMPORT_PATH == *"production"* ]]; then
                echo "Error: Not allowed to do these operation in production."
                exit 1
         fi
         read -p "This will create a backup for existing database before import. Continue? (y/n): " CONFIRM_DB_BACKUP
         if [ "$CONFIRM_DB_BACKUP" == "y" ] || [ "$CONFIRM_DB_BACKUP" == "Y" ]; then
            ssh ${IMPORT_PATH} "mkdir -p ${BACKUP_FOLDER} && mysqldump -u\$(php -r \"echo (include('app/etc/env.php'))['db']['connection']['default']['username'];\") -p\$(php -r \"echo (include('app/etc/env.php'))['db']['connection']['default']['password'];\") -h\$(php -r \"echo (include('app/etc/env.php'))['db']['connection']['default']['host'];\") --single-transaction \$(php -r \"echo (include('app/etc/env.php'))['db']['connection']['default']['dbname'];\") magento_logging_event | sed -e 's/DEFINER=[^*]*\*/\*/g' | gzip > ${BACKUP_FOLDER}${DB_FILE}"
            if [ $? -ne 0 ]; then
                echo "Error creating the backup."
                exit 1
            fi
        fi
        read -p "Import the extracted database to remote instance. Continue? (y/n): " CONFIRM_DB_IMPORT
        if [ "$CONFIRM_DB_IMPORT" == "y" ] || [ "$CONFIRM_DB_IMPORT" == "Y" ]; then
            ssh ${IMPORT_PATH} "cat ${BACKUP_FOLDER}${IMPORT_FILE} | mysql -u\$(php -r \"echo (include('app/etc/env.php'))['db']['connection']['default']['username'];\") -p\$(php -r \"echo (include('app/etc/env.php'))['db']['connection']['default']['password'];\") -h\$(php -r \"echo (include('app/etc/env.php'))['db']['connection']['default']['host'];\") \$(php -r \"echo (include('app/etc/env.php'))['db']['connection']['default']['dbname'];\")"
            if [ $? -ne 0 ]; then
                    echo "Error importing the file."
                    exit 1
            fi
            read -p "After database import do you want delete sql file. Continue? (y/n): " IMPORT_DELETE_FILE
            if [ "$IMPORT_DELETE_FILE" == "y" ] || [ "$IMPORT_DELETE_FILE" == "Y" ]; then
                    if [ -n "$IMPORT_FILE" ]; then
                        ssh ${IMPORT_PATH} rm "$BACKUP_FOLDER$IMPORT_FILE"
                        echo "Remote file deleted: $BACKUP_FOLDER$IMPORT_FILE"
                    fi
                fi
         fi
        else
            echo "It's not allowed to do these operation in Local."
            exit 0
        fi

    echo "File successfully imported: $IMPORT_FILE"
fi

# Handle copy operation
if [ "$COPY_FLAG" = true ]; then
    if [ -z "$SOURCE_SERVER" ] || [ -z "$SOURCE_PATH" ] || [ -z "$DESTINATION_SERVER" ] || [ -z "$DESTINATION_PATH" ]; then
        echo "Error: Source and destination server and paths are required for copy operation."
        exit 1
    fi
    if [[ $DESTINATION_SERVER == *"production"* ]]; then
        echo "Error: Not allowed to do these operation in production."
        exit 1
    fi
    echo "Copying file from $SOURCE_SERVER:$SOURCE_PATH to $DESTINATION_SERVER:$DESTINATION_PATH..."
    scp "$SOURCE_SERVER:$SOURCE_PATH" "$DESTINATION_SERVER:$DESTINATION_PATH"
    if [ $? -ne 0 ]; then
        echo "Error copying the file. Check the logs for details."
        exit 1
    fi
    echo "File successfully copied from $SOURCE_SERVER:$SOURCE_PATH to $DESTINATION_SERVER:$DESTINATION_PATH"
fi
