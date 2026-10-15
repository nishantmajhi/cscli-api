/* Unit 10: File Handling in C
 * Random access in a file using fseek/ftell - jump directly to a
 * specific record instead of reading sequentially. */
#include <stdio.h>

struct Student {
    char name[50];
    int roll;
};

int main(void) {
    struct Student students[3] = {
        {"Aarav", 1}, {"Bina", 2}, {"Chetan", 3}
    };

    FILE *fp = fopen("students_multi.dat", "wb");
    if (fp == NULL) {
        printf("Error opening file!\n");
        return 1;
    }
    fwrite(students, sizeof(struct Student), 3, fp);
    fclose(fp);

    fp = fopen("students_multi.dat", "rb");
    if (fp == NULL) {
        printf("Error opening file!\n");
        return 1;
    }

    /* Jump directly to the 3rd record (index 2) without reading 1 and 2 */
    struct Student target;
    fseek(fp, 2 * sizeof(struct Student), SEEK_SET);
    fread(&target, sizeof(struct Student), 1, fp);

    printf("Record at index 2: %s, roll %d\n", target.name, target.roll);

    fclose(fp);
    return 0;
}
