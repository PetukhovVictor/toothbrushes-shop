scriptPath="/app/engine/application/services/catalog/"
scriptName="buildCache.php"

while [[ $# -gt 1 ]]
do
    key="$1"
    case $key in
        -c|--field)
            field="$2"
            shift
        ;;
        *)
        ;;
    esac
    shift
done

if [ -z "$field" ]
then
    echo "ERROR: field not specified" 1>&2
    exit 1
fi

cd "../..$scriptPath"
php buildCache.php $field