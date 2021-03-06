PORTNAME=			msmtp
PORTVERSION=	1.4.27
PORTREVISION=	0
CATEGORIES=		mail ipv6
COMMENT=			A SMTP client
MAINTAINER=		michael.zoon@freenas.org

MASTER_SITES=			SF

USE_BZIP2=				yes
GNU_CONFIGURE=		yes
CPPFLAGS+=	-I${LOCALBASE}/include
LDFLAGS+=	-L${LOCALBASE}/lib
CONFIGURE_ENV=	CPPFLAGS="${CPPFLAGS}" LDFLAGS="${LDFLAGS}"


OPTIONS=	GNUTLS "Adds GnuTLS support" on \
		OPENSSL "Adds support for SSL (Secure Socket Layer)" on \
		GSASL "Adds GSASL support" off \
		NLS "Native Language Support via gettext utilities" on \
		IDN "Enable Internationalized Domain Names" off \

.include       <bsd.port.options.mk>

.if defined(WITH_GNUTLS)
LIB_DEPENDS+=   gnutls.47:${PORTSDIR}/security/gnutls
CONFIGURE_ARGS+=	--with-ssl=gnutls
.elif defined(WITH_OPENSSL)
USE_OPENSSL=	yes
CONFIGURE_ARGS+=	--with-ssl=openssl
CONFIGURE_ENV+=		libssl_CFLAGS="-I${OPENSSLINC}" libssl_LIBS="-L${OPENSSLLIB} -lssl"
.else
CONFIGURE_ARGS+=	--without-ssl
.endif

.if defined(WITH_GNUTLS) || defined(WITH_OPENSSL)
RUN_DEPENDS+=	${LOCALBASE}/share/certs/ca-root-nss.crt:${PORTSDIR}/security/ca_root_nss
.endif

.if defined(WITH_GSASL)
LIB_DEPENDS+=	gsasl.15:${PORTSDIR}/security/gsasl
CONFIGURE_ARGS+=	--with-libgsasl
.else
CONFIGURE_ARGS+=	--without-libgsasl
.endif

.if !defined(WITHOUT_IDN)
LIB_DEPENDS+=	idn.17:${PORTSDIR}/dns/libidn
CONFIGURE_ARGS+=	--with-libidn
.else
CONFIGURE_ARGS+=	--without-libidn
.endif

.if !defined(WITHOUT_NLS)
USE_GETTEXT=		yes
CONFIGURE_ARGS+=	--enable-nls
PLIST_SUB=		NLS=""
.else
CONFIGURE_ARGS+=	--disable-nls
PLIST_SUB=		NLS="@comment "
.endif


do-install:
	@${INSTALL_PROGRAM} -v ${WRKSRC}/src/${PORTNAME} ${FREENAS_ROOTFS}/usr/local/bin
	@${INSTALL_SCRIPT} -v ${FILESDIR}/${PORTNAME}.in ${FREENAS_ROOTFS}/etc/rc.d/${PORTNAME}

.include <bsd.port.mk>
