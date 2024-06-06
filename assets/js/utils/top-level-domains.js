const TOP_LEVEL_DOMAINS = [
    'AAA',
    'AARP',
    'ABARTH',
    'ABB',
    'ABBOTT',
    'ABBVIE',
    'ABC',
    'ABLE',
    'ABOGADO',
    'ABUDHABI',
    'AC',
    'ACADEMY',
    'ACCENTURE',
    'ACCOUNTANT',
    'ACCOUNTANTS',
    'ACO',
    'ACTOR',
    'AD',
    'ADAC',
    'ADS',
    'ADULT',
    'AE',
    'AEG',
    'AERO',
    'AETNA',
    'AF',
    'AFL',
    'AFRICA',
    'AG',
    'AGAKHAN',
    'AGENCY',
    'AI',
    'AIG',
    'AIRBUS',
    'AIRFORCE',
    'AIRTEL',
    'AKDN',
    'AL',
    'ALFAROMEO',
    'ALIBABA',
    'ALIPAY',
    'ALLFINANZ',
    'ALLSTATE',
    'ALLY',
    'ALSACE',
    'ALSTOM',
    'AM',
    'AMAZON',
    'AMERICANEXPRESS',
    'AMERICANFAMILY',
    'AMEX',
    'AMFAM',
    'AMICA',
    'AMSTERDAM',
    'ANALYTICS',
    'ANDROID',
    'ANQUAN',
    'ANZ',
    'AO',
    'AOL',
    'APARTMENTS',
    'APP',
    'APPLE',
    'AQ',
    'AQUARELLE',
    'AR',
    'ARAB',
    'ARAMCO',
    'ARCHI',
    'ARMY',
    'ARPA',
    'ART',
    'ARTE',
    'AS',
    'ASDA',
    'ASIA',
    'ASSOCIATES',
    'AT',
    'ATHLETA',
    'ATTORNEY',
    'AU',
    'AUCTION',
    'AUDI',
    'AUDIBLE',
    'AUDIO',
    'AUSPOST',
    'AUTHOR',
    'AUTO',
    'AUTOS',
    'AVIANCA',
    'AW',
    'AWS',
    'AX',
    'AXA',
    'AZ',
    'AZURE',
    'BA',
    'BABY',
    'BAIDU',
    'BANAMEX',
    'BANANAREPUBLIC',
    'BAND',
    'BANK',
    'BAR',
    'BARCELONA',
    'BARCLAYCARD',
    'BARCLAYS',
    'BAREFOOT',
    'BARGAINS',
    'BASEBALL',
    'BAZAR',
    'BASKETBALL',
    'BAUHAUS',
    'BAYERN',
    'BB',
    'BBC',
    'BBS',
    'BBT',
    'BBVA',
    'BCG',
    'BCN',
    'BD',
    'BE',
    'BEATS',
    'BEAUTY',
    'BEER',
    'BENTLEY',
    'BERLIN',
    'BEST',
    'BESTBUY',
    'BET',
    'BF',
    'BG',
    'BH',
    'BHARTI',
    'BI',
    'BIBLE',
    'BID',
    'BIKE',
    'BING',
    'BINGO',
    'BIO',
    'BIZ',
    'BJ',
    'BLACK',
    'BLACKFRIDAY',
    'BLOCKBUSTER',
    'BLOG',
    'BLOOMBERG',
    'BLUE',
    'BM',
    'BMS',
    'BMW',
    'BN',
    'BNPPARIBAS',
    'BO',
    'BOATS',
    'BOEHRINGER',
    'BOFA',
    'BOM',
    'BOND',
    'BOO',
    'BOOK',
    'BOOKING',
    'BOSCH',
    'BOSTIK',
    'BOSTON',
    'BOT',
    'BOUTIQUE',
    'BOX',
    'BR',
    'BRADESCO',
    'BRIDGESTONE',
    'BROADWAY',
    'BROKER',
    'BROTHER',
    'BRUSSELS',
    'BS',
    'BT',
    'BUGATTI',
    'BUILD',
    'BUILDERS',
    'BUSINESS',
    'BUY',
    'BUZZ',
    'BV',
    'BW',
    'BY',
    'BZ',
    'BZH',
    'CA',
    'CAB',
    'CAFE',
    'CAL',
    'CALL',
    'CALVINKLEIN',
    'CAM',
    'CAMERA',
    'CAMP',
    'CANCERRESEARCH',
    'CANON',
    'CAPETOWN',
    'CAPITAL',
    'CAPITALONE',
    'CAR',
    'CARAVAN',
    'CARDS',
    'CARE',
    'CAREER',
    'CAREERS',
    'CARS',
    'CASA',
    'CASE',
    'CASH',
    'CASINO',
    'CAT',
    'CATERING',
    'CATHOLIC',
    'CBA',
    'CBN',
    'CBRE',
    'CBS',
    'CC',
    'CD',
    'CENTER',
    'CEO',
    'CERN',
    'CF',
    'CFA',
    'CFD',
    'CG',
    'CH',
    'CHAN',
    'CHANEL',
    'CHANNEL',
    'CYB',
    'CHARITY',
    'CHASE',
    'CHAT',
    'CHEAP',
    'CHINTAI',
    'CHRISTMAS',
    'CHROME',
    'CHURCH',
    'CI',
    'CIPRIANI',
    'CIRCLE',
    'CISCO',
    'CITADEL',
    'CITI',
    'CITIC',
    'CITY',
    'CITYEATS',
    'CK',
    'CL',
    'CLAIMS',
    'CLEANING',
    'CLICK',
    'CLINIC',
    'CLINIQUE',
    'CLOTHING',
    'CLOUD',
    'CLUB',
    'CLUBMED',
    'CM',
    'CN',
    'CO',
    'COACH',
    'CODES',
    'COFFEE',
    'COLLEGE',
    'COLOGNE',
    'COM',
    'COMCAST',
    'COMMBANK',
    'COMMUNITY',
    'COMPANY',
    'COMPARE',
    'COMPUTER',
    'COMSEC',
    'CONDOS',
    'CONSTRUCTION',
    'CONSULTING',
    'CONTACT',
    'CONTRACTORS',
    'COOKING',
    'COOKINGCHANNEL',
    'COOL',
    'COOP',
    'CORSICA',
    'COUNTRY',
    'COUPON',
    'COIN',
    'COUPONS',
    'COURSES',
    'CPA',
    'CR',
    'CREDIT',
    'CREDITCARD',
    'CREDITUNION',
    'CRICKET',
    'CROWN',
    'CRS',
    'CRUISE',
    'CRUISES',
    'CU',
    'CUISINELLA',
    'CV',
    'CW',
    'CX',
    'CY',
    'CYMRU',
    'CYOU',
    'CZ',
    'DABUR',
    'DAD',
    'DANCE',
    'DATA',
    'DATE',
    'DATING',
    'DATSUN',
    'DAY',
    'DCLK',
    'DDS',
    'DE',
    'DEAL',
    'DEALER',
    'DEALS',
    'DEGREE',
    'DELIVERY',
    'DELL',
    'DELOITTE',
    'DELTA',
    'DEMOCRAT',
    'DENTAL',
    'DENTIST',
    'DESI',
    'DESIGN',
    'DEV',
    'DHL',
    'DIAMONDS',
    'DIET',
    'DIGITAL',
    'DIRECT',
    'DIRECTORY',
    'DISCOUNT',
    'DISCOVER',
    'DISH',
    'DIY',
    'DJ',
    'DK',
    'DM',
    'DNP',
    'DO',
    'DOCS',
    'DYN',
    'DOCTOR',
    'DOG',
    'DOMAINS',
    'DOT',
    'DOWNLOAD',
    'DRIVE',
    'DTV',
    'DUBAI',
    'DUNLOP',
    'DUPONT',
    'DURBAN',
    'DVAG',
    'DVR',
    'DZ',
    'EARTH',
    'EAT',
    'EC',
    'ECO',
    'EDEKA',
    'EDU',
    'EDUCATION',
    'EE',
    'EG',
    'EMAIL',
    'EMC',
    'EMERCK',
    'ENERGY',
    'ENGINEER',
    'ENGINEERING',
    'ENTERPRISES',
    'EPSON',
    'EQUIPMENT',
    'ER',
    'ERICSSON',
    'ERNI',
    'ES',
    'ESQ',
    'ESTATE',
    'ET',
    'ETISALAT',
    'EU',
    'EUROVISION',
    'EUS',
    'EVENTS',
    'EXCHANGE',
    'EXPERT',
    'EXPOSED',
    'EXPRESS',
    'EXTRASPACE',
    'FAGE',
    'FAIL',
    'FAIRWINDS',
    'FAITH',
    'FAMILY',
    'FAN',
    'FANS',
    'FARM',
    'FARMERS',
    'FASHION',
    'FAST',
    'FEDEX',
    'FEEDBACK',
    'FERRARI',
    'FERRERO',
    'FI',
    'FIAT',
    'FIDELITY',
    'FIDO',
    'FILM',
    'FINAL',
    'FINANCE',
    'FINANCIAL',
    'FIRE',
    'FIRESTONE',
    'FIRMDALE',
    'FISH',
    'FISHING',
    'FIT',
    'FITNESS',
    'FJ',
    'FK',
    'FLICKR',
    'FLIGHTS',
    'FLIR',
    'FLORIST',
    'FLOWERS',
    'FLY',
    'FM',
    'FO',
    'FOO',
    'FOOD',
    'FOODNETWORK',
    'FOOTBALL',
    'FORD',
    'FOREX',
    'FORSALE',
    'FORUM',
    'FOUNDATION',
    'FOX',
    'FR',
    'FREE',
    'FRESENIUS',
    'FRL',
    'FROGANS',
    'FRONTDOOR',
    'FRONTIER',
    'FTR',
    'FUJITSU',
    'FUN',
    'FUR',
    'FUND',
    'FURNITURE',
    'FUTBOL',
    'FYI',
    'GA',
    'GAL',
    'GALLERY',
    'GALLO',
    'GALLUP',
    'GAME',
    'GAMES',
    'GAP',
    'GARDEN',
    'GAY',
    'GB',
    'GBIZ',
    'GD',
    'GDN',
    'GE',
    'GEA',
    'GENT',
    'GEEK',
    'GENTING',
    'GEORGE',
    'GOPHER',
    'GF',
    'GG',
    'GGEE',
    'GH',
    'GI',
    'GIFT',
    'GIFTS',
    'GIVES',
    'GIVING',
    'GL',
    'GLASS',
    'GLE',
    'GLOBAL',
    'GLOBO',
    'GM',
    'GMAIL',
    'GMBH',
    'GMO',
    'GMX',
    'GN',
    'GODADDY',
    'GOLD',
    'GOLDPOINT',
    'GOLF',
    'GOO',
    'GOODYEAR',
    'GOOG',
    'GOOGLE',
    'GOP',
    'GOT',
    'GOV',
    'GP',
    'GQ',
    'GR',
    'GRAINGER',
    'GRAPHICS',
    'GRATIS',
    'GREEN',
    'GRIPE',
    'GROCERY',
    'GROUP',
    'GS',
    'GT',
    'GU',
    'GUARDIAN',
    'GUCCI',
    'GUGE',
    'GUIDE',
    'GUITARS',
    'GURU',
    'GW',
    'GY',
    'HAIR',
    'HAMBURG',
    'HANGOUT',
    'HAUS',
    'HBO',
    'HDFC',
    'HDFCBANK',
    'HEALTH',
    'HEALTHCARE',
    'HELP',
    'HELSINKI',
    'HERE',
    'HERMES',
    'HGTV',
    'HIPHOP',
    'HISAMITSU',
    'HITACHI',
    'HIV',
    'HK',
    'HKT',
    'HM',
    'HN',
    'HOCKEY',
    'HOLDINGS',
    'HOLIDAY',
    'HOMEDEPOT',
    'HOMEGOODS',
    'HOMES',
    'HOMESENSE',
    'HONDA',
    'HORSE',
    'HOSPITAL',
    'HOST',
    'HOSTING',
    'HOT',
    'HOTELES',
    'HOTELS',
    'HOTMAIL',
    'HOUSE',
    'HOW',
    'HR',
    'HSBC',
    'HT',
    'HU',
    'HUGHES',
    'HYATT',
    'HYUNDAI',
    'IBM',
    'ICBC',
    'ICE',
    'ICU',
    'ID',
    'IE',
    'IEEE',
    'IFM',
    'IKANO',
    'IL',
    'IM',
    'IMAMAT',
    'IMDB',
    'IMMO',
    'IMMOBILIEN',
    'IN',
    'INC',
    'INDUSTRIES',
    'INFINITI',
    'INFO',
    'ING',
    'INK',
    'INDY',
    'INSTITUTE',
    'INSURANCE',
    'INSURE',
    'INT',
    'INTERNATIONAL',
    'INTEL',
    'INTUIT',
    'INVESTMENTS',
    'IO',
    'IPIRANGA',
    'IQ',
    'IR',
    'IRISH',
    'IS',
    'ISMAILI',
    'IST',
    'ISTANBUL',
    'IT',
    'ITAU',
    'ITV',
    'JAGUAR',
    'JAVA',
    'JCB',
    'JE',
    'JEEP',
    'JETZT',
    'JEWELRY',
    'JIO',
    'JLL',
    'JM',
    'JMP',
    'JNJ',
    'JO',
    'JOBS',
    'JOBURG',
    'JOT',
    'JOY',
    'JP',
    'JPMORGAN',
    'JPRS',
    'JUEGOS',
    'JUNIPER',
    'KAUFEN',
    'KDDI',
    'KE',
    'KERRYHOTELS',
    'KERRYLOGISTICS',
    'KERRYPROPERTIES',
    'KFH',
    'KG',
    'KH',
    'KI',
    'KIA',
    'KIDS',
    'KIM',
    'KINDER',
    'KINDLE',
    'KITCHEN',
    'KIWI',
    'KM',
    'KN',
    'KO',
    'KOELN',
    'KOMATSU',
    'KOSHER',
    'KP',
    'KPMG',
    'KPN',
    'KR',
    'KRD',
    'KRED',
    'KUOKGROUP',
    'KU',
    'KW',
    'KY',
    'KYOTO',
    'KZ',
    'LA',
    'LACAIXA',
    'LAMBORGHINI',
    'LAMER',
    'LANCASTER',
    'LANCIA',
    'LAND',
    'LANDROVER',
    'LANXESS',
    'LASALLE',
    'LAT',
    'LATINO',
    'LATROBE',
    'LAW',
    'LAWYER',
    'LB',
    'LC',
    'LDS',
    'LEASE',
    'LECLERC',
    'LEFRAK',
    'LEGAL',
    'LEGO',
    'LEXUS',
    'LGBT',
    'LI',
    'LIDL',
    'LIFE',
    'LIFEINSURANCE',
    'LIFESTYLE',
    'LIGHTING',
    'LIKE',
    'LILLY',
    'LIMITED',
    'LIMO',
    'LINCOLN',
    'LINDE',
    'LINK',
    'LIPSY',
    'LIVE',
    'LIVING',
    'LK',
    'LIBRE',
    'LIB',
    'LLC',
    'LLP',
    'LOAN',
    'LOANS',
    'LOCKER',
    'LOCUS',
    'LOFT',
    'LOL',
    'LONDON',
    'LOTTE',
    'LOTTO',
    'LOVE',
    'LPL',
    'LPLFINANCIAL',
    'LR',
    'LS',
    'LT',
    'LTD',
    'LTDA',
    'LU',
    'LUNDBECK',
    'LUXE',
    'LUXURY',
    'LV',
    'LY',
    'MA',
    'MACYS',
    'MADRID',
    'MAIF',
    'MAISON',
    'MAKEUP',
    'MAN',
    'MANAGEMENT',
    'MANGO',
    'MAP',
    'MARKET',
    'MARKETING',
    'MARKETS',
    'MARRIOTT',
    'MARSHALLS',
    'MASERATI',
    'MATTEL',
    'MBA',
    'MC',
    'MCKINSEY',
    'MD',
    'ME',
    'MED',
    'MEDIA',
    'MEET',
    'MELBOURNE',
    'MEME',
    'MEMORIAL',
    'MEN',
    'MENU',
    'MERCKMSD',
    'MG',
    'MH',
    'MIAMI',
    'MICROSOFT',
    'MIL',
    'MINI',
    'MINT',
    'MIT',
    'MITSUBISHI',
    'MK',
    'ML',
    'MLB',
    'MLS',
    'MM',
    'MMA',
    'MN',
    'MO',
    'MOBI',
    'MOBILE',
    'MODA',
    'MOE',
    'MOI',
    'MOM',
    'MONASH',
    'MONEY',
    'MONSTER',
    'MORMON',
    'MORTGAGE',
    'MOSCOW',
    'MOTO',
    'MOTORCYCLES',
    'MOV',
    'MOVIE',
    'MP',
    'MQ',
    'MR',
    'MS',
    'MSD',
    'MT',
    'MTN',
    'MTR',
    'MU',
    'MUSEUM',
    'MUSIC',
    'MUTUAL',
    'MV',
    'MW',
    'MX',
    'MY',
    'MZ',
    'NA',
    'NAB',
    'NAGOYA',
    'NAME',
    'NATURA',
    'NAVY',
    'NBA',
    'NC',
    'NE',
    'NEC',
    'NET',
    'NEO',
    'NULL',
    'NETBANK',
    'NETFLIX',
    'NETWORK',
    'NEUSTAR',
    'NEW',
    'NEWS',
    'NEXT',
    'NEXTDIRECT',
    'NEXUS',
    'NF',
    'NFL',
    'NG',
    'NGO',
    'NHK',
    'NI',
    'NICO',
    'NIKE',
    'NIKON',
    'NINJA',
    'NISSAN',
    'NISSAY',
    'NL',
    'NO',
    'NOKIA',
    'NORTHWESTERNMUTUAL',
    'NORTON',
    'NOW',
    'NOWRUZ',
    'NOWTV',
    'NP',
    'NR',
    'NRA',
    'NRW',
    'NTT',
    'NU',
    'NYC',
    'NZ',
    'O',
    'OSS',
    'OZ',
    'OBI',
    'OBSERVER',
    'OFFICE',
    'OKINAWA',
    'OLAYAN',
    'OLAYANGROUP',
    'OLDNAVY',
    'OLLO',
    'OM',
    'OMEGA',
    'ONE',
    'ONG',
    'ONL',
    'ONLINE',
    'OOO',
    'OPEN',
    'ORACLE',
    'ORANGE',
    'ORG',
    'ORGANIC',
    'ORIGINS',
    'OSAKA',
    'OTSUKA',
    'OTT',
    'OVH',
    'PA',
    'PARODY',
    'PIRATE',
    'PAGE',
    'PANASONIC',
    'PARIS',
    'PARS',
    'PARTNERS',
    'PARTS',
    'PARTY',
    'PASSAGENS',
    'PAY',
    'PCCW',
    'PE',
    'PET',
    'PF',
    'PFIZER',
    'PG',
    'PH',
    'PHARMACY',
    'PHD',
    'PHILIPS',
    'PHONE',
    'PHOTO',
    'PHOTOGRAPHY',
    'PHOTOS',
    'PHYSIO',
    'PICS',
    'PICTET',
    'PICTURES',
    'PID',
    'PIN',
    'PING',
    'PINK',
    'PIONEER',
    'PIZZA',
    'PK',
    'PL',
    'PLACE',
    'PLAY',
    'PLAYSTATION',
    'PLUMBING',
    'PLUS',
    'PM',
    'PN',
    'PNC',
    'POHL',
    'POKER',
    'POLITIE',
    'PORN',
    'POST',
    'PR',
    'PRAMERICA',
    'PRAXI',
    'PRESS',
    'PRIME',
    'PRO',
    'PROD',
    'PRODUCTIONS',
    'PROF',
    'PROGRESSIVE',
    'PROMO',
    'PROPERTIES',
    'PROPERTY',
    'PROTECTION',
    'PRU',
    'PRUDENTIAL',
    'PS',
    'PT',
    'PUB',
    'PW',
    'PWC',
    'PY',
    'QA',
    'QPON',
    'QUEBEC',
    'QUEST',
    'RACING',
    'RADIO',
    'RE',
    'READ',
    'REALESTATE',
    'REALTOR',
    'REALTY',
    'RECIPES',
    'RED',
    'REDSTONE',
    'REDUMBRELLA',
    'REHAB',
    'REISE',
    'REISEN',
    'REIT',
    'RELIANCE',
    'REN',
    'RENT',
    'RENTALS',
    'REPAIR',
    'REPORT',
    'REPUBLICAN',
    'REST',
    'RESTAURANT',
    'REVIEW',
    'REVIEWS',
    'REXROTH',
    'RICH',
    'RICHARDLI',
    'RICOH',
    'RIL',
    'RIO',
    'RIP',
    'RO',
    'ROCHER',
    'ROCKS',
    'RODEO',
    'ROGERS',
    'RM',
    'ROOM',
    'RS',
    'RSVP',
    'RU',
    'RUGBY',
    'RUHR',
    'RUN',
    'RW',
    'RWE',
    'RYUKYU',
    'SA',
    'SAARLAND',
    'SAFE',
    'SAFETY',
    'SAKURA',
    'SALE',
    'SALON',
    'SAMSCLUB',
    'SAMSUNG',
    'SANDVIK',
    'SANDVIKCOROMANT',
    'SANOFI',
    'SAP',
    'SARL',
    'SAS',
    'SAVE',
    'SAXO',
    'SB',
    'SBI',
    'SBS',
    'SC',
    'SCA',
    'SCB',
    'SCHAEFFLER',
    'SCHMIDT',
    'SCHOLARSHIPS',
    'SCHOOL',
    'SCHULE',
    'SCHWARZ',
    'SCIENCE',
    'SCOT',
    'SD',
    'SE',
    'SEARCH',
    'SEAT',
    'SECURE',
    'SECURITY',
    'SEEK',
    'SELECT',
    'SENER',
    'SERVICES',
    'SES',
    'SEVEN',
    'SEW',
    'SEX',
    'SEXY',
    'SFR',
    'SG',
    'SH',
    'SHANGRILA',
    'SHARP',
    'SHAW',
    'SHELL',
    'SHIA',
    'SHIKSHA',
    'SHOES',
    'SHOP',
    'SHOPPING',
    'SHOUJI',
    'SHOW',
    'SHOWTIME',
    'SI',
    'SILK',
    'SINA',
    'SINGLES',
    'SITE',
    'SJ',
    'SK',
    'SKI',
    'SKIN',
    'SKY',
    'SKYPE',
    'SL',
    'SLING',
    'SM',
    'SMART',
    'SMILE',
    'SN',
    'SNCF',
    'SO',
    'SOCCER',
    'SOCIAL',
    'SOFTBANK',
    'SOFTWARE',
    'SOHU',
    'SOLAR',
    'SOLUTIONS',
    'SONG',
    'SONY',
    'SOY',
    'SPA',
    'SPACE',
    'SPORT',
    'SPOT',
    'SR',
    'SRL',
    'SS',
    'ST',
    'STADA',
    'STAPLES',
    'STAR',
    'STATEBANK',
    'STATEFARM',
    'STC',
    'STCGROUP',
    'STOCKHOLM',
    'STORAGE',
    'STORE',
    'STREAM',
    'STUDIO',
    'STUDY',
    'STYLE',
    'SU',
    'SUCKS',
    'SUPPLIES',
    'SUPPLY',
    'SUPPORT',
    'SURF',
    'SURGERY',
    'SUZUKI',
    'SV',
    'SWATCH',
    'SWISS',
    'SX',
    'SY',
    'SYDNEY',
    'SYSTEMS',
    'SZ',
    'TAB',
    'TAIPEI',
    'TALK',
    'TAOBAO',
    'TARGET',
    'TATAMOTORS',
    'TATAR',
    'TATTOO',
    'TAX',
    'TAXI',
    'TC',
    'TCI',
    'TD',
    'TDK',
    'TEAM',
    'TE',
    'TECH',
    'TECHNOLOGY',
    'TEL',
    'TEMASEK',
    'TENNIS',
    'TEVA',
    'TF',
    'TG',
    'TH',
    'THD',
    'THEATER',
    'THEATRE',
    'TI',
    'TIAA',
    'TICKETS',
    'TIENDA',
    'TIFFANY',
    'TIPS',
    'TIRES',
    'TIROL',
    'TJ',
    'TJMAXX',
    'TJX',
    'TK',
    'TKMAXX',
    'TL',
    'TM',
    'TMALL',
    'TN',
    'TO',
    'TODAY',
    'TOKYO',
    'TOOLS',
    'TOP',
    'TORAY',
    'TOSHIBA',
    'TOTAL',
    'TOURS',
    'TOWN',
    'TOYOTA',
    'TOYS',
    'TR',
    'TRADE',
    'TRADING',
    'TRAINING',
    'TRAVEL',
    'TRAVELCHANNEL',
    'TRAVELERS',
    'TRAVELERSINSURANCE',
    'TRUST',
    'TRV',
    'TT',
    'TUBE',
    'TUI',
    'TUNES',
    'TUSHU',
    'TV',
    'TVS',
    'TW',
    'TZ',
    'UA',
    'UU',
    'UBANK',
    'UBS',
    'UG',
    'UK',
    'UNICOM',
    'UNIVERSITY',
    'UNO',
    'UOL',
    'UPS',
    'US',
    'UY',
    'UZ',
    'VA',
    'VACATIONS',
    'VANA',
    'VANGUARD',
    'VC',
    'VE',
    'VEGAS',
    'VENTURES',
    'VERISIGN',
    'VERSICHERUNG',
    'VET',
    'VG',
    'VI',
    'VIAJES',
    'VIDEO',
    'VIG',
    'VIKING',
    'VILLAS',
    'VIN',
    'VIP',
    'VIRGIN',
    'VISA',
    'VISION',
    'VIVA',
    'VIVO',
    'VLAANDEREN',
    'VN',
    'VODKA',
    'VOLKSWAGEN',
    'VOLVO',
    'VOTE',
    'VOTING',
    'VOTO',
    'VOYAGE',
    'VU',
    'VUELOS',
    'WALES',
    'WALMART',
    'WALTER',
    'WANG',
    'WANGGOU',
    'WATCH',
    'WATCHES',
    'WEATHER',
    'WEATHERCHANNEL',
    'WEBCAM',
    'WEBER',
    'WEBSITE',
    'WED',
    'WEDDING',
    'WEIBO',
    'WEIR',
    'WF',
    'WHOSWHO',
    'WIEN',
    'WIKI',
    'WILLIAMHILL',
    'WIN',
    'WINDOWS',
    'WINE',
    'WINNERS',
    'WME',
    'WOLTERSKLUWER',
    'WOODSIDE',
    'WORK',
    'WORKS',
    'WORLD',
    'WOW',
    'WS',
    'WTC',
    'WTF',
    'XBOX',
    'XEROX',
    'XFINITY',
    'XIHUAN',
    'XIN',
    'XXX',
    'XYZ',
    'YACHTS',
    'YAHOO',
    'YAMAXUN',
    'YANDEX',
    'YE',
    'YODOBASHI',
    'YOGA',
    'YOKOHAMA',
    'YOU',
    'YOUTUBE',
    'YT',
    'YUN',
    'ZA',
    'ZAPPOS',
    'ZARA',
    'ZERO',
    'ZIP',
    'ZM',
    'ZONE',
    'ZUERICH',
    'ZW',
    'ZIPPO',
];
export default TOP_LEVEL_DOMAINS;