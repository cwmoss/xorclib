# where is the xorc directory?
LIB=./

# where is the doc directory?
DOC=htdocs/doc

BASE=$(shell pwd)

REVISION=$(shell cat ${LIB}/VERSION)

DISTDIR=xorc-${REVISION}
BSF=xorc-${REVISION}




all:
	@echo ""
	@echo "available targets are:"
	@echo "   dist (for making the package)"
	@echo "on with da xorc..."
	@echo "=-"
	@echo ""

dist:
	@if [ -d ${DISTDIR} ] ; then echo "DISTDIR exists already"; exit 1; fi
#	@if [ -e ".filelist" ] ; then echo "stalled .filelist?"; exit 1; fi
#	ls ${LIB} > .filelist
#	ls ${DOC} >> .filelist
	mkdir ${DISTDIR}
	chmod 755 ${DISTDIR}
#	cp -Raf `cat .filelist` ${DISTDIR}

	cp -Rf ${BASE}/${LIB} ${DISTDIR}/

	cp -Rf ${BASE}/${DOC} ${DISTDIR}/

	echo "RELEASE ${REVISION}" > ${DISTDIR}/VERSION
#	rm .filelist
	find ${DISTDIR} -name CVS -type d | xargs rm -rf
	find ${DISTDIR} -name \*~ -type f | xargs rm -rf
	find ${DISTDIR} -name \*.bak -type f | xargs rm -rf
	tar cvfz ${BSF}.tgz ${DISTDIR} > /dev/null
	rm -rf ${DISTDIR}

xdist:
	svn status -v|egrep '^ '|sed -e 's/\s.*\s.*\s.*\s\(.*\)$$/\1/g' -e 1d > .filelist
	mkdir ${DISTDIR}
	chmod 755 ${DISTDIR}
	cp --parents `cat .filelist` ${DISTDIR} || echo ".. copy fehler ignorieren!"
	echo "RELEASE ${REVISION}" > ${DISTDIR}/VERSION
	tar cvfz ${BSF}.tgz ${DISTDIR} > /dev/null
	rm -rf ${DISTDIR}
    
pear:
	svn status -v > .filelistpear
	php -f bin/pear_package.php .filelistpear
	cd ../;pear convert package.xml; cd xorc;
	php -f bin/pear_package.php ../package2.xml
	cd ../;pear package package2.xml; cd xorc
