#---------------------------
# This script generates a new pmpro.pot file for use in translations.
# To generate a new pmpro.pot, cd to the main /paid-memberships-pro/ directory,
# then execute `languages/gettext.sh` from the command line.
#---------------------------
echo "Updating pmpro.pot... "
xgettext -j -o languages/pmpro.pot \
--default-domain=pmpro \
--language=PHP \
--keyword=_ \
--keyword=__ \
--keyword=_e \
--keyword=_ex \
--keyword=_n \
--keyword=_x \
--sort-by-file \
--package-version=1.0 \
--msgid-bugs-address="jason@strangerstudios.com" \
$(find . -name "*.php")
echo "Done!"