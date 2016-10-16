#!/usr/bin/env bash

while [[ $# -gt 1 ]]
do
    key="$1"
    case $key in
        -t|--titles)
            titles="$2"
            shift
        ;;
        -d|--descriptions)
            descriptions="$2"
            shift
        ;;
        -p|--prices)
            prices="$2"
            shift
        ;;
        -i|--images)
            images="$2"
            shift
        ;;
        -h|--host)
            host="$2"
            shift
        ;;
        -n|--number)
            number="$2"
            shift
        ;;
        *)
        ;;
    esac
    shift
done

if [ -z "$titles" ]
then
    echo "ERROR: titles file not specified" 1>&2
    exit 1
fi

if [ -z "$descriptions" ]
then
    echo "ERROR: descriptions file not specified" 1>&2
    exit 1
fi

if [ -z "$prices" ]
then
    echo "ERROR: prices file not specified" 1>&2
    exit 1
fi

if [ -z "$images" ]
then
    echo "ERROR: images file not specified" 1>&2
    exit 1
fi

if [ -z "$number" ]
then
    number=100
fi

if [ -z "$host" ]
then
    host=$HOSTNAME
fi

titlesTexts=()

while read -r line
do
    titlesTexts+=("$line")
done < "$titles"

descriptionsTexts=()
while read -r line
do
    descriptionsTexts+=("$line")
done < "$descriptions"

pricesTexts=()
while read -r line
do
    pricesTexts+=("$line")
done < "$prices"

imagesTexts=()
while read -r line
do
    imagesTexts+=("$line")
done < "$images"

i=1
while [ $i -le $number ]; do
    randomTitle=${titlesTexts[$RANDOM % ${#titlesTexts[@]} ]}
    randomDescription=${descriptionsTexts[$RANDOM % ${#descriptionsTexts[@]} ]}
    randomPrice=${pricesTexts[$RANDOM % ${#pricesTexts[@]} ]}
    randomImage=${imagesTexts[$RANDOM % ${#imagesTexts[@]} ]}
    ./add.sh -t "${randomTitle}" -d "${randomDescription}" -p "${randomPrice}" -i "${randomImage}" -h "${host}"
    i=$(expr $i + 1)
done &