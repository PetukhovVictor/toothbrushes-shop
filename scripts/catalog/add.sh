#!/usr/bin/env bash

path="/catalog/add"

while [[ $# -gt 1 ]]
do
    key="$1"
    case $key in
        -t|--title)
            title="$2"
            shift
        ;;
        -d|--description)
            description="$2"
            shift
        ;;
        -p|--price)
            price="$2"
            shift
        ;;
        -i|--image)
            image="$2"
            shift
        ;;
        -h|--host)
            host="$2"
            shift
        ;;
        *)
        ;;
    esac
    shift
done

if [ -z "$title" ]
then
    echo "ERROR: title not specified" 1>&2
    exit 1
fi

if [ -z "$description" ]
then
    echo "ERROR: description not specified" 1>&2
    exit 1
fi

if [ -z "$price" ]
then
    echo "ERROR: price not specified" 1>&2
    exit 1
fi

if [ -z "$image" ]
then
    echo "ERROR: image not specified" 1>&2
    exit 1
fi

if [ -z "$host" ]
then
    host=$HOSTNAME
fi

result=$(curl -s --request POST ${host}${path} --data-urlencode "title=${title}" --data-urlencode "description=${description}" --data "price=${price}" --data-urlencode "image=${image}")
status_code=$(echo ${result} | python -c "import sys, json; print json.load(sys.stdin)['status_code']")

if [ "$status_code" -eq 0 ]
then
    id=$(echo ${result} | python -c "import sys, json; print json.load(sys.stdin)['data'][0]['id']")
    echo "SUCCESS: item inserted with id = ${id}"
else
    echo "ERROR: status code = $status_code"
fi