DESTHOST=technik@<appname>.20sec.de
DESTPATH=/var/www/<appname>
DEST=$(DESTHOST):$(DESTPATH)
SYNCOPTS=-avz --exclude="._*" --exclude=".DS_*" --exclude=".svn" 

all: conf/validations.db conf/msg-*.db js

%.css: %.scss	
	sassc $< $@

%.db: %.yaml
	bin/<appname> yaml-to-db $<

js: 
	npm install ; grunt


sync: sync-forum
	
sync-forum: 
	#rsync $(SYNCOPTS) --delete --exclude="perch/resources" flarum/ $(DEST)/htdocs/forum/

sync-public:
	#rsync $(SYNCOPTS) public/ $(DEST)/htdocs/

sync-includes:
	#rsync $(SYNCOPTS) --exclude="*.data" --exclude='*.html' include/ $(DEST)/include/

sync-templates:
	#rsync $(SYNCOPTS) --exclude="*.data" templates/ $(DEST)/templates/

sync-app: 
	#rsync $(SYNCOPTS) -CR --exclude="xorc" --exclude="slugs.txt" app/public/index.php app/public/css app/public/js app/public/gfx app/bin app/lib app/conf app/src app/db $(DEST)/app/

sync-xorc: 
	#rsync $(SYNCOPTS) -C ../xorc $(DEST)/app/lib/

fetch-templates:
	#rsync $(SYNCOPTS) --exclude="*.data" --exclude='*.html' $(DEST)/templates/ templates/

fetch-includes:
	#rsync $(SYNCOPTS) --exclude="*.data" $(DEST)/include/ include/
